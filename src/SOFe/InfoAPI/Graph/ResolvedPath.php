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

use Closure;
use SOFe\InfoAPI\Info;

/**
 * A path found in `Graph::pathFind`.
 */
final class ResolvedPath {
	/** @phpstan-var array<int, Closure(Info): ?Info> */
	private array $resolvers;
	private EdgeWeight $weight;
	/** @phpstan-var class-string<Info> */
	private string $tail;

	/**
	 * @phpstan-param class-string<Info> $tail
	 */
	public function __construct(string $tail) {
		$this->resolvers = [];
		$this->weight = new EdgeWeight;
		$this->tail = $tail;
	}

	/**
	 * @phpstan-return array<int, Closure>
	 */
	public function getResolvers() : array {
		return $this->resolvers;
	}

	public function getWeight() : EdgeWeight {
		return $this->weight;
	}

	/**
	 * Returns the info class that this path ends at.
	 *
	 * @phpstan-return class-string<Info>
	 */
	public function getTail() : string {
		return $this->tail;
	}

	/**
	 * @phpstan-param Closure(Info): ?Info $resolver
	 * @phpstan-param class-string<Info>        $newTail
	 */
	public function join(Closure $resolver, bool $isFallback, string $newTail) : ResolvedPath {
		$path = clone $this;
		$path->resolvers[] = $resolver;
		$path->weight = clone $path->weight;
		if($isFallback) {
			++$path->weight->fallback;
		} else {
			++$path->weight->parentChild;
		}
		$path->tail = $newTail;
		return $path;
	}

	public function resolve(Info $info) : ?Info {
		foreach($this->resolvers as $resolver) {
			$info = $resolver($info);
			if($info === null) {
				return null;
			}
		}
		return $info;
	}
}
