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

use Generator;
use SOFe\InfoAPI\Ast\ChildName;

final class EdgeList {
	/** @phpstan-var array<string, array<int, ListedEdge>> */
	private array $edges = [];

	/** @phpstan-var array<int, ListedEdge> */
	private array $fallbacks = [];

	public function insert(ListedEdge $edge) : void {
		$name = $edge->edge->getName();
		if($name !== null) {
			$last = $name->getLastPart();
			if(!isset($this->edges[$last])) {
				$this->edges[$last] = [];
			}
			$this->edges[$last][] = $edge;
		} else {
			$this->fallbacks[] = $edge;
		}
	}

	/**
	 * Returns all edges matching the pattern, as well as all fallbacks.
	 *
	 * @phpstan-return Generator<int, ListedEdge, void, void>
	 */
	public function find(ChildName $pattern) : Generator {
		$last = $pattern->getLastPart();
		if(!isset($this->edges[$last])) {
			return;
		}

		foreach($this->edges[$last] as $edge) {
			$name = $edge->edge->getName();
			if($name === null || $name->matches($pattern)) {
				yield $edge;
			}
		}

		yield from $this->fallbacks;
	}

	/**
	 * @phpstan-return array<string, array<int, ListedEdge>>
	 */
	public function getEdges() : array {
		return $this->edges;
	}

	/**
	 * @phpstan-return array<int, ListedEdge>
	 */
	public function getFallbacks() : array {
		return $this->fallbacks;
	}

	/**
	 * @phpstan-return Generator<mixed, ListedEdge, mixed, mixed>
	 */
	public function iterAllEdges() : Generator {
		foreach($this->edges as $array) {
			yield from $array;
		}
		yield from $this->fallbacks;
	}
}
