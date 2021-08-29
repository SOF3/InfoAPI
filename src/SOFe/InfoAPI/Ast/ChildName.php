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

namespace SOFe\InfoAPI\Ast;

use count;

final class ChildName {
	/** @phpstan-var non-empty-array<int, string> */
	private array $parts;

	/**
	 * @phpstan-param non-empty-array<int, string> $parts
	 */
	private function __construct(array $parts) {
		$this->parts = $parts;
	}

	/**
	 * Parses a field used to identify a parent-child relationship.
	 */
	static public function parse(string $fqn) : self {
		return new self(explode(".", $fqn));
	}

	/**
	 * Checks if this child name matches the pattern required.
	 *
	 * A pattern is matched if and only if the part sequence in this name
	 * is a subsequence (certain items removed without changing the order)
	 * of the part sequence of the pattern name,
	 * AND the last parts of this child name and the pattern are the same.
	 */
	public function matches(ChildName $pattern) : bool {
		if($this->parts[count($this->parts) - 1] !== $pattern->parts[count($pattern->parts) - 1]) {
			return false;
		}

		$match = 0;

		for($req = 0; $req < count($pattern->parts); ++$req) {
			while($this->parts[$match] !== $pattern->parts[$req]) {
				++$match;
				if($match >= count($this->parts)) {
					return false;
				}
			}
			++$match;
		}

		return true;
	}

	/**
	 * @phpstan-return non-empty-array<int, string>
	 */
	public function getParts() : array {
		return $this->parts;
	}

	public function getLastPart() : string {
		return $this->parts[count($this->parts) - 1];
	}
}
