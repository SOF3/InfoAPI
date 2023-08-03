<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Template;

use Closure;
use Generator;
use RuntimeException;
use SOFe\AwaitGenerator\Await;
use SOFe\AwaitGenerator\Traverser;

use function count;
use function implode;
use function is_string;

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

	public function staticElement(string $raw) : RenderedElement {
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
