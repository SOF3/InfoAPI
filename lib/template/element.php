<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Template;

use pocketmine\command\CommandSender;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use SOFe\AwaitGenerator\Traverser;

use function count;
use function sprintf;

interface TemplateElement {
	/**
	 * @template R of RenderedElement
	 * @template G of RenderedGroup
	 * @param GetOrWatch<R, G> $getOrWatch
	 * @return R
	 */
	public function render(mixed $context, ?CommandSender $sender, GetOrWatch $getOrWatch) : RenderedElement;
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

	private function populateChain(mixed $context, NestedEvalChain $chain, bool $display, ?CommandSender $sender) : bool {
		foreach ($this->choices as $choice) {
			// initialize starting state
			$chain->then(
				fn($_) => [$context, []],
				null,
			);

			foreach ($choice->path->segments as $segment) {
				// TODO optimization: make these argument triggers parallel instead of serial
				foreach($segment->args as $arg) {
					if($arg->path !== null) {
						$child = new StackedEvalChain($chain);
						$arg->path->populateChain($context, $child, false, null);
						$child->finish(function($state, $argResult) {
							/** @var array{mixed, mixed[]} $state */
							[$receiver, $args] = $state;
							$args[] = $argResult;
							return [$receiver, $args];
						});
					} else {
						$chain->then(function($state) use($arg) {
							/** @var array{mixed, mixed[]} $state */
							[$receiver, $args] = $state;
							$args[] = $arg->constantValue;
							return [$receiver, $args];
						}, null);
					}
				}

				$chain->then(
					function($state) use($segment) {
						/** @var array{mixed, mixed[]} $state */
						return [($segment->mapping->map)($state[0], $state[1]), []];
					},
					$segment->mapping->subscribe === null ? null : fn($state) => new Traverser(($segment->mapping->subscribe)($state[0], $state[1])),
				);
			}

			if($display) {
				$chain->then(
					function($state) use($choice, $sender) {
						/** @var array{mixed, mixed[]} $state */
						return $state[0] !== null ? ($choice->display->display)($state[0], $sender) : null;
					},
					null,
				);

				if($chain->breakOnNonNull()) {
					return true;
				}
			}
		}

		return false;
	}

	public function render(mixed $context, ?CommandSender $sender, GetOrWatch $getOrWatch) : RenderedElement {
		$chain = $getOrWatch->startEvalChain(); // double dispatch
		if($this->populateChain($context, $chain, true, $sender)) {
			return $chain->getResultAsElement();
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
	public function __construct(
		public ResolvedPath $path,
		public Display $display,
	) {
	}
}
