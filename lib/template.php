<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Template;

use Closure;
use Generator;
use pocketmine\command\CommandSender;
use RuntimeException;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use SOFe\AwaitGenerator\Await;
use SOFe\AwaitGenerator\InterruptException;
use SOFe\AwaitGenerator\Traverser;
use SOFe\InfoAPI\Ast;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\Pathfind;
use SOFe\InfoAPI\Pathfind\Path;
use SOFe\InfoAPI\QualifiedRef;
use Throwable;

use function array_map;
use function array_push;
use function array_slice;
use function count;
use function implode;
use function sprintf;
use function strlen;
use function substr;

final class Template {
	public static function fromAst(Ast\Template $ast, Indices $indices, string $sourceKind) : Template {
		$self = new self;

		/** @var Ast\RawText|Ast\Expr $element */
		foreach ($ast->elements as $element) {
			if ($element instanceof Ast\RawText) {
				$self->elements[] = new RawText($element->original);
			} else {
				$choices = [];
				for ($expr = $element; $expr !== null; $expr = $expr->else) {
					$path = self::resolveInfoPath($indices, $expr->main, $sourceKind, fn(string $kind) : bool => $indices->displays->canDisplay($kind));
					if ($path !== null) {
						$display = $indices->displays->getDisplay($path->tailKind) ?? throw new RuntimeException("canDisplay admitted tail kind");
						$choices[] = new PathWithDisplay($path->mappings, $display);
					}
				}

				$raw = self::toRawString($element->main);

				$self->elements[] = new CoalescePath($raw, $choices);
			}
		}

		return $self;
	}

	/**
	 * @param Closure(string): bool $admitTailKind
	 */
	private static function resolveInfoPath(Indices $indices, Ast\InfoExpr $infoExpr, string $sourceKind, Closure $admitTailKind) : ?Path {
		$calls = self::extractMappingCalls($infoExpr);
		$paths = Pathfind\Finder::find($indices, $calls, $sourceKind, $admitTailKind);
		// TODO resolve parameter paths
		return count($paths) > 0 ? $paths[0] : null;
	}

	private static function toRawString(Ast\InfoExpr $infoExpr) : string {
		$calls = self::extractMappingCalls($infoExpr);
		$callStrings = array_map(fn(QualifiedRef $name) => implode(Mapping::FQN_SEPARATOR, $name->tokens), $calls);
		return implode(" ", $callStrings);
	}

	/**
	 * @return QualifiedRef[]
	 */
	private static function extractMappingCalls(Ast\InfoExpr $path) : array {
		$calls = [];
		if ($path->parent !== null) {
			$calls = self::extractmappingCalls($path->parent);
		}
		$calls[] = $path->call->name;
		return $calls;
	}

	private function __construct() {
	}

	/** @var TemplateElement[] */
	private array $elements = [];

	/**
	 * @template R of RenderedElement
	 * @template G of RenderedGroup
	 * @template T of GetOrWatch<R, G>
	 * @param T $getOrWatch
	 * @return G
	 */
	public function render(mixed $context, ?CommandSender $sender, GetOrWatch $getOrWatch) : RenderedGroup {
		$elements = [];

		foreach ($this->elements as $element) {
			$elements[] = $element->render($context, $sender, $getOrWatch);
		}

		return $getOrWatch->buildResult($elements);
	}
}

/**
 * @template R of RenderedElement
 * @template G of RenderedGroup
 */
interface GetOrWatch {
	/**
	 * @param R[] $elements
	 * @return G
	 */
	public function buildResult(array $elements) : RenderedGroup;

	/**
	 * @return EvalChain<R>
	 */
	public function startEvalChain() : EvalChain;
}

/**
 * @template R of RenderedElement
 */
interface EvalChain {
	/**
	 * Add a step in the chain to map the return value of the previous step.
	 * The first step receives null.
	 *
	 * @param Closure(mixed): mixed $map
	 */
	public function then(Closure $map, ?Closure $updateTrigger);

	/**
	 * Returns true if the inference is non-watching and the last step returned non-null.
	 */
	public function breakOnNonNull() : bool;

	/**
	 * Returns a RenderedElement that performs the steps executed in this chain so far.
	 */
	public function getResultAsElement() : RenderedElement;
}

interface RenderedElement {
}

interface RenderedGroup {
}

interface TemplateElement {
	public function render(mixed $context, ?CommandSender $sender, GetOrWatch $getOrWatch) : RenderedElement;
}

/**
 * @implements GetOrWatch<RenderedGetElement, RenderedGetGroup>
 */
final class Get implements GetOrWatch {
	public function build(array $elements) : RenderedGroup {
		$rendered = [];
		foreach ($elements as $element) {
			$rendered[] = $element->get();
		}
		return new RenderedGetGroup($rendered);
	}

	public function startEvalChain() : EvalChain {
		return new GetEvalChain;
	}
}

/**
 * @implements EvalChain<RenderedGetElement>
 */
final class GetEvalChain implements EvalChain {
	private mixed $state = null;

	public function then(Closure $map, ?Closure $_trigger) : void {
		$this->state = $map($this->state);
	}

	public function breakOnNonNull() : bool {
		return $this->state !== null;
	}

	public function getResultAsElement() : RenderedElement {
		if(!is_string($this->state)) {
			throw new RuntimeException("Last mapper must return string");
		}
		return new StaticRenderedElement($this->state);
	}
}

interface RenderedGetElement extends RenderedElement {
	public function get() : string;
}

final class RenderedGetGroup implements RenderedGroup {
	/**
	 * @param RenderedGetElement[] $elements
	 */
	public function __construct(private array $elements) {
	}

	public function get() : string {
		$output = "";
		foreach ($this->elements as $element) {
			$output .= $element->get();
		}
		return $output;
	}
}

/**
 * @implements GetOrWatch<RenderedWatchElement, RenderedWatchGroup>
 */
final class Watch implements GetOrWatch {
	public function build(array $elements) : RenderedGroup {
		$rendered = [];
		foreach ($elements as $element) {
			$rendered[] = $element->get();
		}
		return new RenderedWatchGroup($rendered);
	}

	public function startEvalChain() : EvalChain {
		return new GetEvalChain;
	}
}

/**
 * @implements EvalChain<RenderedWatchElement>
 */
final class WatchEvalChain implements EvalChain {
	/** @var (Closure(mixed): mixed)[] */
	private array $maps = [];
	/** @var array<int, Closure(): Traverswr<null>> */
	private array $subFuncs	= [];
	/** @var mixed[] */
	private array $values = [];
	/** @var list<?Traverswr<null>> */
	private array $traversers = [];

	public function then(Closure $map, ?Closure $_trigger) : void {
		$this->state = $map($this->state);
	}

	public function breakOnNonNull() : bool {
		return $this->state !== null;
	}

	public function getResultAsElement() : RenderedElement {
		if(!is_string($this->state)) {
			throw new RuntimeException("Last mapper must return string");
		}
		return new StaticRenderedElement($this->state);
	}
}

interface RenderedWatchElement extends RenderedElement {
	/**
	 * @return Traverser<string>
	 */
	public function watch() : Traverser;
}

final class RawText implements TemplateElement, RenderedGetElement, RenderedWatchElement {
	public function __construct(public string $raw) {
	}

	public function render(mixed $context, ?CommandSender $sender, GetOrWatch $getOrWatch) : RenderedElement {
		return $this;
	}

	public function get() : string {
		return $this->raw;
	}

	public function watch() : Traverser {
		return Traverser::fromClosure(function() {
			yield $this->raw => Traverser::VALUE;
		});
	}
}

final class CoalescePath implements TemplateElement {
	/**
	 * @param string $raw Fallback display if the string cannot be resolved
	 * @param PathWithDisplay[] $choices
	 */
	public function __construct(
		public string $raw,
		public array $choices,
	) {
	}

	public function render(mixed $context, ?CommandSender $sender, GetOrWatch $getOrWatch) : RenderedElement {
		$chain = $getOrWatch->startEvalChain(); // double dispatch

		foreach ($this->choices as $choice) {
			$chain->then(
				fn($_) => $context,
				null,
			);

			foreach ($choice->path as $mapping) {
				$args = []; // TODO

				$chain->then(
					fn($receiver) => ($mapping->map)($receiver, $args),
					$mapping->subscribe === null ? null : fn($receiver) => ($mapping->subscribe)($receiver, $args),
				);
			}

			if ($chain->breakOnNonNull()) {
				return $chain->getResultAsElement();
			}
		}

		$chain->then(
			fn($_) => sprintf(
				"{%s:%s}",
				count($this->choices) === 0 ? "unknownPath" : "null", // error message
				$this->raw,
			),
			null,
		);
	}
}

final class PathWithDisplay {
	/**
	 * @param Mapping[] $path
	 */
	public function __construct(
		public array $path,
		public Display $display,
	) {
	}

	public function run(mixed $context, ?CommandSender $sender, ?UpdateTrigger $updateTrigger) : ?string {
		$receiver = $context;

		foreach ($this->path as $mapping) {
			$args = []; // TODO
			$receiver = ($mapping->map)($receiver, $args);
			if ($mapping->subscribe !== null) {
				$traverser = ($mapping->subscribe)($receiver, $args);
				$group = new UpdateTriggerGroup($traverser);
				$updateTrigger->push($group);
			}
		}

		if ($receiver === null) {
			// special logic for coalescence
			return null;
		}

		return ($this->display->display)($receiver, $sender);
	}
}

final class UpdateTrigger {
	/** @var UpdateTriggerGroup[] */
	public array $updateChain = [];

	public function __construct(
		public int $elementIndex, // offset = elementOffsets[elementIndex]
	) {
	}

	/**
	 * Suspend until one of the groups is triggered.
	 *
	 * Returns the index of the triggered group $k.
	 * The caller is expected to replace the subsequent groups ($k+1 onwards) immediately.
	 *
	 * @return Generator<mixed, mixed, mixed, int>
	 */
	public function next() : Generator {
		$racers = [];
		foreach ($this->updateChain as $k => $group) {
			$racers[$k] = $group->next();
		}

		[$k,] = yield from Await::safeRace($racers);
		return $k;
	}

	/**
	 * Interrupts and removes the traversers from $inclusive onwards with $newTail.
	 *
	 * Does not update any existing `next()` calls.
	 * This is expected to be called after `next()` returns.
	 *
	 * @return Generator<mixed, mixed, mixed, void>
	 */
	public function dropAfter(int $inclusive) : Generator {
		$dropped = array_slice($this->updateChain, $inclusive);
		$this->updateChain = array_slice($this->updateChain, 0, $inclusive);
		foreach ($dropped as $group) {
			$thrown = new InterruptException;
			try {
				yield from $group->interrupt($thrown);
			} catch(InterruptException $caught) {
				if ($thrown !== $caught) {
					throw $caught;
				}
			}
		}
	}

	/**
	 * Appends traversers to the end of the chain.
	 *
	 * Does not update any existing `next()` calls.
	 * This is expected to be called after `next()` returns.
	 */
	public function push(UpdateTriggerGroup ...$tail) {
		array_push($this->updateChain, $tail);
	}
}

final class UpdateTriggerGroup {
	/**
	 * @param Traverser<null>[] $traversers
	 */
	public function __construct(
		public array $traversers,
	) {
	}

	/**
	 * Suspend until one of the traversers in this group is triggered.
	 *
	 * @return Generator<mixed, mixed, mixed, void>
	 */
	public function next() : Generator {
		$racers = [];
		foreach ($this->traversers as $traverser) {
			$racers[] = $traverser->next($_);
		}

		yield from Await::safeRace($racers);
	}

	/**
	 * Terminates all triggers in this group.
	 *
	 * @return Generator<mixed, mixed, mixed, void>
	 */
	public function interrupt(Throwable $ex) : Generator {
		$otherEx = $ex;

		foreach ($this->traversers as $traverser) {
			try {
				yield from $traverser->interrupt($ex);
			} catch(Throwable $caught) {
				if ($caught !== $ex) {
					$otherEx = $caught;
				}
			}
		}

		throw $otherEx;
	}
}

final class RenderResult {
	public Traverser $subscribe;

	/**
	 * @param array<int, int> $elementOffsets
	 * @param array<int, UpdateTrigger> $updateTriggers
	 */
	public function __construct(
		public string $formatted,
		public array $elementOffsets,
		private array $updateTriggers,
	) {
		$this->subscribe = new Traverser($this->loop());
	}

	public function loop() : Generator {
		try {
			while (true) {
				$racers = [];
				foreach ($this->updateTriggers as $index => $trigger) {
					$racers[$index] = $trigger->next();
				}
				[$index, $groupNumber] = yield from Await::safeRace($racers);

				$this->updateTriggers[$index]->dropAfter($groupNumber + 1);

				$elementOutput = [];


				$startOffset = $this->elementOffsets[$index];
				$endOffset = $this->elementOffsets[$index + 1] ?? strlen($this->formatted);
				$this->formatted = substr($this->formatted, 0, $startOffset) . $elementOutput . substr($this->formatted, $endOffset);

				yield $this->formatted => Traverser::VALUE;
			}
		} finally {
			foreach ($updateTriggers as $trigger) {
				yield from $trigger->dropAfter(0);
			}
		}
	}
}
