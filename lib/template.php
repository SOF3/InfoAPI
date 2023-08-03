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
use SOFe\AwaitGenerator\Traverser;
use SOFe\InfoAPI\Ast;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\Pathfind;
use SOFe\InfoAPI\Pathfind\Path;
use SOFe\InfoAPI\QualifiedRef;

use function array_map;
use function count;
use function implode;
use function is_string;
use function sprintf;

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
			$rendered = $element->render($context, $sender, $getOrWatch);
			$elements[] = $rendered;
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

	/**
	 * @return R
	 */
	public function staticElement(string $raw) : RenderedElement;
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
	 * @param ?Closure(mixed): Traverser<null> $subscribe
	 */
	public function then(Closure $map, ?Closure $subscribe) : void;

	/**
	 * Returns true if the inference is non-watching and the last step returned non-null.
	 */
	public function breakOnNonNull() : bool;

	/**
	 * Returns a RenderedElement that performs the steps executed in this chain so far.
	 *
	 * @return R
	 */
	public function getResultAsElement() : RenderedElement;
}

interface RenderedElement {
}

interface RenderedGroup {
}

interface TemplateElement {
	/**
	 * @template R of RenderedElement
	 * @template G of RenderedGroup
	 * @param GetOrWatch<R, G> $getOrWatch
	 * @return R
	 */
	public function render(mixed $context, ?CommandSender $sender, GetOrWatch $getOrWatch) : RenderedElement;
}

/**
 * @implements GetOrWatch<RenderedGetElement, RenderedGetGroup>
 */
final class Get implements GetOrWatch {
	public function buildResult(array $elements) : RenderedGroup {
		$rendered = [];
		foreach ($elements as $element) {
			$rendered[] = $element;
		}
		return new RenderedGetGroup($rendered);
	}

	public function startEvalChain() : EvalChain {
		return new GetEvalChain;
	}

	public function staticElement(string $raw): RenderedElement {
		return new StaticRenderedElement($raw);
	}
}

/**
 * @implements EvalChain<RenderedGetElement>
 */
final class GetEvalChain implements EvalChain {
	private mixed $state = null;

	public function then(Closure $map, ?Closure $subscribe) : void {
		$this->state = $map($this->state);
	}

	public function breakOnNonNull() : bool {
		return $this->state !== null;
	}

	public function getResultAsElement() : RenderedElement {
		if (!is_string($this->state)) {
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
	public function buildResult(array $elements) : RenderedGroup {
		return new RenderedWatchGroup($elements);
	}

	public function startEvalChain() : EvalChain {
		return new WatchEvalChain;
	}

	public function staticElement(string $raw): RenderedElement
	{
		return new StaticRenderedElement($raw);
	}
}

/**
 * @implements EvalChain<RenderedWatchElement>
 */
final class WatchEvalChain implements EvalChain, RenderedWatchElement {
	private int $counter = 0;

	/** @var list<Closure(mixed): mixed> */
	private array $maps = [];
	/** @var array<int, Closure(mixed): Traverser<null>> */
	private array $subFuncs = [];

	/** @var array<int, true> */
	private array $breakpoints = [];

	/** @var mixed[] */
	private array $values = [];
	/** @var list<?Traverser<null>> */
	private array $traversers = [];

	public function then(Closure $map, ?Closure $subFunc) : void {
		$index = $this->counter++;

		$this->maps[$index] = $map;
		if ($subFunc !== null) {
			$this->subFuncs[$index] = $subFunc;
		}
	}

	public function breakOnNonNull() : bool {
		$this->breakpoints[$this->counter] = true;
		return false;
	}

	public function getResultAsElement() : RenderedElement {
		return $this;
	}

	public function watch() : Traverser {
		return Traverser::fromClosure(function() {
			try {
				while (true) {
					yield $this->getOnce() => Traverser::VALUE;

					$racers = [];
					foreach ($this->traversers as $k => $traverser) {
						if ($traverser !== null) {
							$racers[$k] = $traverser->next($_);
						}
					}

					[$k, $running] = yield from Await::safeRace($racers);
					if ($running) {
						yield from $this->truncateTraversers($k);
					} else {
						// finalized traverser, no updates
						$this->traversers[$k] = null;
					}
				}
			} finally {
				yield from $this->truncateTraversers(0);
			}
		});
	}

	private function truncateTraversers(int $min) : Generator {
		for ($index = $min; $index < $this->counter; $index++) {
			unset($this->values[$index]);
			if (isset($this->traversers[$index])) {
				yield from $this->traversers[$index]->interrupt();
			}
			unset($this->traversers[$index]);
		}
	}

	private function getOnce() : string {
		for ($index = 0; $index < $this->counter; $index++) {
			$prev = $index > 0 ? $this->values[$index - 1] : null;

			if (isset($this->breakpoints[$index])) {
				if ($index > 0 && is_string($prev)) {
					return $this->values[$index - 1];
				}
			}

			if (!isset($this->values[$index])) {
				$this->values[$index] = ($this->maps[$index])($prev);

				$trigger = isset($this->subFuncs[$index]) ? $this->subFuncs[$index]($prev) : null;
				$this->traversers[$index] = $trigger;
			}
		}

		$last = $this->values[$this->counter - 1];
		if (!is_string($last)) {
			throw new RuntimeException("EvalChain::watch() cannot be called before a final then() to conclude errors");
		}

		return $last;
	}
}

interface RenderedWatchElement extends RenderedElement {
	/**
	 * @return Traverser<string>
	 */
	public function watch() : Traverser;
}

final class RenderedWatchGroup implements RenderedGroup {
	/**
	 * @param RenderedWatchElement[] $elements
	 */
	public function __construct(private array $elements) {
	}

	/**
	 * @return Traverser<string>
	 */
	public function watch() : Traverser {
		return Traverser::fromClosure(function() {
			$traversers = [];
			try {
				foreach ($this->elements as $element) {
					$traversers[] = $element->watch();
				}

				/** @var array<int, string> $strings */
				$strings = [];
				while (true) {
					/** @var Generator<mixed, mixed, mixed, bool>[] $racers */
					$racers = [];
					foreach ($traversers as $k => $traverser) {
						if ($traverser !== null) {
							$racers[$k] = $traverser->next($strings[$k]);
						}
					}

					[$k, $running] = yield from Await::safeRace($racers);
					if (!$running) {
						// no more updates in this traverser (currently unreachable, but let's support this case anyway)
						unset($traversers[$k]);
						continue;
					}

					if (count($strings) === count($this->elements)) {
						yield implode("", $strings) => Traverser::VALUE;
					}
				}
			} finally {
				foreach ($traversers as $traverser) {
					yield from $traverser->interrupt();
				}
			}
		});
	}
}

final class RawText implements TemplateElement {
	public function __construct(public string $raw) {
	}

	public function render(mixed $context, ?CommandSender $sender, GetOrWatch $getOrWatch) : RenderedElement {
		return $getOrWatch->staticElement($this->raw);
	}
}

final class StaticRenderedElement implements RenderedGetElement, RenderedWatchElement {
	public function __construct(private string $raw) {
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
					$mapping->subscribe === null ? null : fn($receiver) => new Traverser(($mapping->subscribe)($receiver, $args)),
				);
			}

			$chain->then(
				fn($receiver) => $receiver !== null ? ($choice->display->display)($receiver, $sender) : null,
				null,
			);

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
		return $chain->getResultAsElement();
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
}
