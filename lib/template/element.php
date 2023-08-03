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
