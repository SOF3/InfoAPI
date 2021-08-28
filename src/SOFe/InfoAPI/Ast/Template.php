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

final class Template {
	/** @phpstan-var array<int, Segment> */
	public $elements;

	/**
	 * Parses a string into a template AST.
	 *
	 * @throws ParseException
	 */
	static public function parse(string $template) : self {
		// TODO implement
	}
}
