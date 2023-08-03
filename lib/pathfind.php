<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Pathfind;

use Closure;
use Shared\SOFe\InfoAPI\Mapping;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\QualifiedRef;
use SplPriorityQueue;
use function array_merge;
use function array_shift;
use function count;
use function in_array;

final class Finder {
	/**
	 * Perform Dijkstra pathfinding on the shortest path with the cost function defined in the Cost class.
	 *
	 * @param QualifiedRef[] $calls
	 * @param Closure(string): bool $admitTailKind
	 */
	public static function find(Indices $indices, array $calls, string $sourceKind, Closure $admitTailKind) {
		$heap = new Heap;
		$heap->insertPath(new Path(
			unreadCalls: $calls,
			tailKind: $sourceKind,
			mappings: [],
			implicitLoopDetector: [$sourceKind],
			cost: new Cost(0, 0),
		));

		/** @var Path[] $accepted */
		$accepted = [];
		while (!$heap->isEmpty()) {
			$path = $heap->extract();

			/** @var Path[] $newPaths */
			$newPaths = [];

			if (count($path->unreadCalls) > 0) {
				$shiftedCalls = $path->unreadCalls;
				$call = array_shift($shiftedCalls);
				$matches = $indices->namedMappings->find($path->tailKind, $call);
				foreach ($matches as $match) {
					// TODO also check parameter compatibility here?
					$newPaths[] = new Path(
						unreadCalls: $shiftedCalls,
						tailKind: $match->mapping->targetKind,
						mappings: array_merge($path->mappings , [$match->mapping]),
						implicitLoopDetector: [$match->mapping->targetKind],
						cost: $path->cost->addMapping($match->score),
					);
				}
			}

			$implicits = $indices->implicitMappings->getImplicit($path->tailKind);
			foreach ($implicits as $implicit) {
				if (in_array($implicit->targetKind, $path->implicitLoopDetector, true)) {
					continue;
				}

				$newPaths[] = new Path(
					unreadCalls: $path->unreadCalls, // $call was not consumed, don't shift
					tailKind: $implicit->targetKind,
					mappings: array_merge($path->mappings , [$implicit]),
					implicitLoopDetector: array_merge($path->implicitLoopDetector , [$implicit->targetKind]),
					cost: $path->cost->addMapping(0),
				);
			}

			foreach ($newPaths as $newPath) {
				if (count($newPath->unreadCalls) === 0 && $admitTailKind($newPath->tailKind)) {
					$accepted[] = $newPath;
				} else {
					$heap->insertPath($newPath);
				}
			}
		}

		return $accepted;
	}
}

final class Path {
	/**
	 * @param QualifiedRef[] $unreadCalls
	 * @param Mapping[] $mappings
	 */
	public function __construct(
		public array $unreadCalls,
		public string $tailKind,
		public array $mappings,
		public array $implicitLoopDetector,
		public Cost $cost,
	) {
	}
}

/**
 * Cost of a path.
 *
 * A path with fewer steps is better than a path with more steps.
 * If two paths have the same number of steps,
 * a path with lower score is better than a path with higher score.
 */
final class Cost {
	public function __construct(
		public int $sumScore,
		public int $numMappings,
	) {
	}

	public function addMapping(int $score) : self {
		return new self(
			sumScore: $this->sumScore + $score,
			numMappings: $this->numMappings + 1,
		);
	}

	public function compare(Cost $that) : int {
		if ($this->numMappings !== $that->numMappings) {
			return $this->numMappings <=> $that->numMappings;
		}

		return $this->sumScore <=> $that->sumScore;
	}
}

/**
 * @extends SplPriorityQueue<Cost, Path>
 */
final class Heap extends SplPriorityQueue {
	public function compare(mixed $priority1, mixed $priority2) : int {
		return $priority1->compare($priority2);
	}

	public function insertPath(Path $path) : void {
		$this->insert($path, $path->cost);
	}
}
