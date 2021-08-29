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

use SOFe\InfoAPI\ParseException;
use function count;
use function explode;
use function strlen;

/**
 * A segment of brace-enclosed info.
 */
final class InfoSegment implements Segment {
	public Expression $head;

	static public function parse(string $string, int $index) : self {
		$head = new Expression;
		$expr = $head;
		$first = true;
		foreach(explode("|", $string) as $pathString) {
			if(!$first) {
				$expr->alternative = new Expression;
				$expr = $expr->alternative;
			}
			$first = false;
			$expr->path = self::parsePath($pathString, $index);

			$index += strlen($pathString) + 1;
		}
		$self = new self;
		$self->head = $head;
		return $self;
	}

	static private function parsePath(string $path, int $index) : Path {
		$names = [];
		foreach(explode(" ", $path) as $part) {
			if(strlen($part) > 0) {
				$names[] = ChildName::parse($part);
			}
		}

		if(count($names) === 0) {
			throw new ParseException("Empty template or coalescence path", $index);
		}

		$path = new Path;
		$path->names = $names;
		return $path;
	}
}
