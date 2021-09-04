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

use AssertionError;
use SOFe\InfoAPI\Graph\Graph;
use SOFe\InfoAPI\Info;

final class Template {
	/** @phpstan-var class-string<Info> */
	private string $source;

	/** @phpstan-var array<int, Prepared\Segment> */
	private array $segments;

	/**
	 * @phpstan-param class-string<Info> $source
	 */
	static public function create(string $template, string $source, Graph $graph) : self {
		$ast = Ast\Template::parse($template);

		$segments = [];

		foreach($ast->segments as $segment) {
			if($segment instanceof Ast\TextSegment) {
				$segments[] = new Prepared\Text($segment->text);
			} elseif($segment instanceof Ast\InfoSegment) {
				$paths = [];
				$expr = $segment->head;
				for($expr = $segment->head; $expr !== null; $expr = $expr->alternative) {
					$names = $expr->path->names;
					$path = $graph->pathFind($source, $names);
					if($path !== null) {
						$paths[] = $path;
					}
				}
				$segments[] = new Prepared\Path($paths);
			} else {
				throw new AssertionError("Ast\\Segment is sealed");
			}
		}

		$self = new self;
		$self->segments = $segments;
		return $self;
	}

	public function resolve(Info $context) : string {
		assert($context instanceof $this->source, "Use of incorrect source context");

		$output = "";
		foreach($this->segments as $segment) {
			$output .= $segment->write($context);
		}

		return $output;
	}
}
