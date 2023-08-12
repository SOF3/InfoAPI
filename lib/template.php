<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Template;

use Closure;
use pocketmine\command\CommandSender;
use RuntimeException;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Parameter;
use SOFe\AwaitGenerator\Traverser;
use SOFe\InfoAPI\Ast;
use SOFe\InfoAPI\Ast\MappingCall;
use SOFe\InfoAPI\Pathfind;
use SOFe\InfoAPI\ReadIndices;

use function array_map;
use function count;
use function implode;

final class Template {
	public static function fromAst(Ast\Template $ast, ReadIndices $indices, string $sourceKind) : Template {
		$self = new self;

		/** @var Ast\RawText|Ast\Expr $element */
		foreach ($ast->elements as $element) {
			if ($element instanceof Ast\RawText) {
				$self->elements[] = new RawText($element->original);
			} else {
				$self->elements[] = self::toCoalescePath($indices, $sourceKind, $element);
			}
		}

		return $self;
	}

	public static function toCoalescePath(ReadIndices $indices, string $sourceKind, Ast\Expr $element) : CoalescePath {
		$choices = [];
		for ($expr = $element; $expr !== null; $expr = $expr->else) {
			$path = self::resolveInfoPath($indices, $expr->main, $sourceKind, fn(string $kind) : bool => $indices->getDisplays()->canDisplay($kind));
			if ($path !== null) {
				$display = $indices->getDisplays()->getDisplay($path->getTargetKind()) ?? throw new RuntimeException("canDisplay admitted tail kind");
				$choices[] = new PathWithDisplay($path, $display);
			}
		}

		$raw = self::toRawString($element->main);

		return new CoalescePath($raw, $choices);
	}

	/**
	 * @param Closure(string): bool $admitTailKind
	 */
	private static function resolveInfoPath(ReadIndices $indices, Ast\InfoExpr $infoExpr, string $sourceKind, Closure $admitTailKind) : ?ResolvedPath {
		$calls = self::extractMappingCalls($infoExpr);
		$paths = Pathfind\Finder::find($indices, array_map(fn(MappingCall $call) => $call->name, $calls), $sourceKind, $admitTailKind);

		if(count($paths) === 0) {
			return null;
		}

		foreach($paths as $path) {
			$segments = [];
			$callIndex = 0;
			if(count($path->mappings) === 0) {
				throw new RuntimeException("path cannot have no mappings");
			}
			foreach($path->mappings as $mapping) {
				$args = [];
				if(!$mapping->isImplicit) {
					$call = $calls[$callIndex];
					$callIndex += 1;


					$args = self::matchArgsToParams($indices, $sourceKind, $calls[$callIndex]->args ?? [], $mapping->parameters);
				}

				$segments[] = new ResolvedPathSegment($mapping, $args);
			}
			return new ResolvedPath($segments);
		}
	}

	private static function toRawString(Ast\InfoExpr $infoExpr) : string {
		$calls = self::extractMappingCalls($infoExpr);
		$callStrings = array_map(fn(MappingCall $call) => implode(Mapping::FQN_SEPARATOR, $call->name->tokens), $calls);
		return implode(" ", $callStrings);
	}

	/**
	 * @return MappingCall[]
	 */
	private static function extractMappingCalls(Ast\InfoExpr $path) : array {
		$calls = [];
		if ($path->parent !== null) {
			$calls = self::extractmappingCalls($path->parent);
		}
		$calls[] = $path->call;
		return $calls;
	}

	/**
	 * @param Ast\Arg[] $astArgs
	 * @param Parameter[] $params
	 * @return ResolvedPathArg[]
	 */
	private static function matchArgsToParams(ReadIndices $indices, string $sourceKind, array $astArgs, array $params) : array {
		$namedParams = [];
		$resolved = [];
		foreach($params as $i => $param) {
			$namedParams[$param->name] = $i;
			$resolved[$i] = ResolvedPathArg::unset($param);
		}
		$nextPositional = range(0, count($params));

		foreach($astArgs as $astArg) {
			$argName = $astArg->name;
			if($argName !== null) {
				$index = $namedParams[$argName];
			} else {
				$index = array_keys($nextPositional)[0];
			}
			$param = $params[$index];
			unset($nextPositional[$index]);

			$resolved[$index] = ResolvedPathArg::fromAst($indices, $sourceKind, $astArg, $param);
		}

		return $resolved;
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

final class ResolvedPath {
	/**
	 * @param non-empty-array<int, ResolvedPathSegment> $segments
	 */
	public function __construct(
		public array $segments,
	) {}

	public function getTargetKind() : string {
		return $this->segments[count($this->segments)-1]->mapping->targetKind;
	}
}

final class ResolvedPathSegment {
	/**
	 * @param list<ResolvedPathArg> $args
	 */
	public function __construct(
		public Mapping $mapping,
		public array $args,
	) {}
}

final class ResolvedPathArg {
	/**
	 * $path and $constantValue are exclusive.
	 */
	private function __construct(
		public Parameter $param,
		public ?CoalescePath $path,
		public mixed $constantValue,
	) {}

	public static function unset(Parameter $param) : self {
		return new self($param, path: null, constantValue: null);
	}
	public static function fromAst(ReadIndices $indices, string $sourceKind, Ast\Arg $astArg, Parameter $param) : self {
		if($astArg->value instanceof Ast\JsonValue) {
			$value = json_decode($astArg->value->json);
			return new self($param, path: null, constantValue: $value);
		} else {
			$expr = $astArg->value;
			$path = Template::toCoalescePath($indices, $sourceKind, $expr);
			return new self($param, path: $path, constantValue: null);
		}
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

interface NestedEvalChain {
	/**
	 * Add a step in the chain to map the return value of the previous step.
	 * The first step receives null.
	 *
	 * @param Closure(mixed): mixed $map
	 * @param ?Closure(mixed): ?Traverser<null> $subscribe
	 */
	public function then(Closure $map, ?Closure $subscribe) : void;

	/**
	 * Returns true if the inference is non-watching and the last step returned non-null.
	 */
	public function breakOnNonNull() : bool;
}

/**
 * @template R of RenderedElement
 */
interface EvalChain extends NestedEvalChain {
	/**
	 * Returns a RenderedElement that performs the steps executed in this chain so far.
	 *
	 * @return R
	 */
	public function getResultAsElement() : RenderedElement;
}

final class StackedEvalChain implements NestedEvalChain {
	public function __construct(private NestedEvalChain $chain) {
		// state: [parentState, isChildBroken, childState]
		$this->chain->then(fn($parentState) => [$parentState, false, null], null);
	}

	public function then(Closure $map, ?Closure $subscribe) : void {
		$this->chain->then(function($state) use($map) {
			/** @var array{mixed, bool, mixed} $state */
			[$parentState, $isBroken, $myState] = $state;
			if($isBroken) return;
			$myState = $map($myState);
			return [$parentState, $myState];
		}, function($state) use($subscribe) {
			/** @var array{mixed, bool, mixed} $state */
			return ($state[1] || $subscribe === null) ? null : $subscribe($state[2]);} );
	}

	public function breakOnNonNull(): bool {
		$this->chain->then(function($state) {
			/** @var array{mixed, bool, mixed} $state */
			[$parentState, $isBroken, $myState] = $state;
			$isBroken = $isBroken || $myState !== null;
			return [$parentState, $isBroken, $myState];
		}, null);
		return false;
	}

	/**
	 * Complete this stack. Merge the stacked result into the original value.
	 */
	public function finish(Closure $merge) : void {
		$this->chain->then(function($state) use($merge) {
			/** @var array{mixed, bool, mixed} $state */
			return $merge($state[0], $state[2]);
		}, null);
	}
}

interface RenderedElement {
}

interface RenderedGroup {
}
