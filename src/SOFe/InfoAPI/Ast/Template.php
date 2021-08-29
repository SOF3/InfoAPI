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

/**
 * @internal Parsing utilities for InfoAPI.
 * Classes under this namespace should not be used externally,
 * and hence are semver-exempt.
 */
namespace SOFe\InfoAPI\Ast;

use function strlen;
use function strpos;
use function substr;
use SOFe\InfoAPI\ParseException;

final class Template {
	/** @phpstan-var array<int, Segment> */
	public $segments = [];

	/**
	 * Parses a string into a template AST.
	 *
	 * @throws ParseException
	 */
	static public function parse(string $template) : self {
		$self = new self;

		$text = new TextSegment;

		for($index = 0; $index < strlen($template); ++$index) {
			if($template[$index] === "{") {
				if($index + 1 < strlen($template) && $template[$index + 1] === "{") {
					++$index;
					$text->text .= "{";
				} else {
					if($text->text !== "") {
						$self->segments[] = $text;
						$text = new TextSegment;
					}
					$until = strpos($template, "}", $index);
					if($until === false) {
						throw new ParseException("Unmatched open brace. Use {{ to write a literal open brace.", $index);
					}
					$self->segments[] = InfoSegment::parse(substr($template, $index + 1, $until - $index - 1));
					$index = $until;
				}
			} elseif($template[$index] === "}") {
				if($index + 1 < strlen($template) && $template[$index + 1] === "}") {
					++$index;
					$text->text .= "}";
				} else {
					throw new ParseException("Unmatched close brace. Use }} to write a literal close brace.", $index);
				}
			} else {
				$text->text .= $template[$index];
			}
		}

		if($text->text !== "") {
			$self->segments[] = $text;
		}

		return $self;
	}
}
