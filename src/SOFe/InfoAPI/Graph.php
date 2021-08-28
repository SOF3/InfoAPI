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

namespace SOFe\InfoAPI;

/**
 * An from-adjacency list used to resolve expressions.
 */
final class Graph {
	/** @phpstan-var array<class-string<Info>, EdgeList> */
	private array $fromIndex = [];

	public function __construct() {}

	/**
	 * Finds all paths starting from `$source` that matches `$expression`.
	 *
	 * @phpstan-var class-string<Info> $source
	 * @phpstan-var array<int, ChildName> $expression
	 * @phpstan-return array<int, ResolvedPath>
	 */
	public function pathFind(string $source, array $expression) : array {
		// TODO implement
	}
}
