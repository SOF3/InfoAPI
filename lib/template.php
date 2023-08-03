<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Template;

use Closure;
use pocketmine\command\CommandSender;
use RuntimeException;
use Shared\SOFe\InfoAPI\Mapping;
use SOFe\AwaitGenerator\Traverser;
use SOFe\InfoAPI\Ast;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\Pathfind;
use SOFe\InfoAPI\Pathfind\Path;
use SOFe\InfoAPI\QualifiedRef;

use function array_map;
use function count;
use function implode;

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
