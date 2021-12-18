<?php

/*
 * InfoAPI
 *
 * Copyright (C) 2019-2021 SOFe
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace SOFe\InfoAPI\Graph;

use function count;
use SOFe\InfoAPI\Ast\ChildName;
use SOFe\InfoAPI\Info;
use SplPriorityQueue;

/**
 * An from-adjacency list used to resolve expressions.
 */
final class Graph {
	/** @phpstan-var array<class-string<Info>, EdgeList> */
	private array $fromIndex = [];

	public function __construct() {}

	/**
	 * Inserts an edge into the graph.
	 *
	 * @phpstan-param class-string<Info> $src
	 * @phpstan-param class-string<Info> $dest
	 */
	public function insert(string $src, string $dest, Edge $edge) : void {
		if(!isset($this->fromIndex[$src])) {
			$this->fromIndex[$src] = new EdgeList;
		}
		$le = new ListedEdge;
		$le->edge = $edge;
		$le->target = $dest;
		$this->fromIndex[$src]->insert($le);
	}

	/**
	 * Finds all paths starting from `$source` that matches `$expression`.
	 *
	 * @phpstan-param class-string<Info> $source
	 * @phpstan-param array<int, ChildName> $expression
	 * @phpstan-return ResolvedPath|null
	 */
	public function pathFind(string $source, array $expression) : ?ResolvedPath {
		$heap = new class extends SplPriorityQueue {
			/**
			 * @param mixed $a
			 * @param mixed $b
			 */
			public function compare($a, $b) : int {
				if($a instanceof EdgeWeight and $b instanceof EdgeWeight) {
					return -EdgeWeight::compare($a, $b);
				}
				return $a <=> $b;
			}
		};
		$heap->insert(new ResolvedPath($source), new EdgeWeight);

		// TODO implement cycle detection
		while(!$heap->isEmpty()) {
			$path = $heap->extract();

			// This is the number of expression segments we have resolved.
			$step = $path->getWeight()->parentChild;
			if($step === count($expression)) {
				return $path;
			}

			if(!isset($this->fromIndex[$path->getTail()])) {
				// This is a dead path.
				continue;
			}

			$list = $this->fromIndex[$path->getTail()];

			foreach($list->find($expression[$step]) as $edge) {
				$newPath = $path->join($edge->edge->getResolver(), $edge->edge->isFallback(), $edge->target);

				$heap->insert($newPath, $newPath->getWeight());
			}
		}

		return null;
	}

	/**
	 * @phpstan-return array<class-string<Info>, EdgeList>
	 */
	public function getFromIndex() : array {
		return $this->fromIndex;
	}
}
