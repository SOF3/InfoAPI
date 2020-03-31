<?php

/*
 * InfoAPI
 *
 * Copyright (C) 2019-2020 SOFe
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

namespace SOFe\InfoAPI;

use Closure;
use function array_slice;
use function count;
use function implode;
use function substr_count;

/**
 * This class is used to store details in the InfoRegistry, only for the purpose of info browsing.
 */
class PrincipalDetail{
	/** @var string[] */
	private $identifiers = [];
	/** @var Closure */
	private $closure;

	/**
	 * @param string[] $identifiers
	 * @param Closure  $closure
	 */
	public function __construct(array $identifiers, Closure $closure){
		$this->identifiers = $identifiers;
		$this->closure = $closure;
	}

	/**
	 * @return string[]
	 */
	public function getIdentifiers() : array{
		return $this->identifiers;
	}

	public function getClosure() : Closure{
		return $this->closure;
	}

	public function increasePrecision(string $name) : string{
		// TODO unverified assumption: $name is always a suffix of implode(".", $this->identifiers)
		$size = substr_count($name, ".") + 1;
		return implode(".", array_slice($this->identifiers, count($this->identifiers) - 1 - $size));
	}
}
