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

namespace SOFe\InfoAPI\Prepared;

use SOFe\InfoAPI\Graph\ResolvedPath;
use SOFe\InfoAPI\Info;

/**
 * A segment of (escapes-resolved) plain-text.
 */
final class Path implements Segment {
	public const NULL_WRITE = "{unknown}"; // TODO any better solution?

	/** @phpstan-var array<int, ResolvedPath> */
	private array $paths;

	/**
	 * @phpstan-param array<int, ResolvedPath> $paths
	 */
	public function __construct(array $paths) {
		$this->paths = $paths;
	}

	public function write(Info $context) : string {
		foreach($this->paths as $path) {
			$info = $path->resolve($context);
			if($info !== null) {
				return $info->toString();
			}
		}
		return self::NULL_WRITE;
	}
}
