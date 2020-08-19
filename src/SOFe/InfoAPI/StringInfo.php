<?php

/*
 * InfoAPI
 *
 * Copyright (C) 2019 SOFe
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

use function function_exists;
use function mb_strtolower;
use function mb_strtoupper;
use function strtolower;
use function strtoupper;

class StringInfo extends Info{
	/** @var string */
	private $string;

	public function __construct(string $string){
		$this->string = $string;
	}

	public function toString() : string{
		return $this->string;
	}

	/**
	 * @param InfoRegistry $registry
	 *
	 * @internal Used by InfoAPI to register details
	 */
	public static function register(InfoRegistry $registry) : void{
		$registry->addDetail("pocketmine.string.uppercase", static function(StringInfo $info){
			return new StringInfo(function_exists("mb_strtoupper") ? mb_strtoupper($info->string) : strtoupper($info->string));
		});
		$registry->addDetail("pocketmine.string.lowercase", static function(StringInfo $info){
			return new StringInfo(function_exists("mb_strtolower") ? mb_strtolower($info->string) : strtolower($info->string));
		});
	}
}
