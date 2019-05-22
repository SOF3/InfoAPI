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

use InvalidArgumentException;
use function abs;
use function gettype;
use function is_numeric;
use function sprintf;

class NumberInfo extends Info{
	/** @var float */
	private $number;

	public function __construct($number){
		if(!is_numeric($number)){
			throw new InvalidArgumentException("Expected numeric value, got " . gettype($number));
		}
		$this->number = (float) $number;
	}

	public function getNumber() : float{
		return $this->number;
	}

	public function toString() : string{
		return sprintf("%g", $this->number);
	}

	/**
	 * @param InfoRegistry $registry
	 *
	 * @internal Used by InfoAPI to register details
	 */
	public static function register(InfoRegistry $registry) : void{
		$registry->addDetail(self::class, "pocketmine.number.ordinal", static function(NumberInfo $info){
			/** @noinspection TypeUnsafeComparisonInspection */
			if($info->number != (int) $info->number){
				return null;
			}
			$number = abs($info->number) % 100;
			if($number !== 11 && $number % 10 === 1){
				$suffix = "st";
			}elseif($number !== 12 && $number % 10 === 2){
				$suffix = "nd";
			}elseif($number !== 13 && $number % 10 === 3){
				$suffix = "rd";
			}else{
				$suffix = "th";
			}
			return new StringInfo($info->number . $suffix);
		});
		$registry->addDetails(self::class, ["pocketmine.number.percent", "pocketmine.number.percentage"], static function(NumberInfo $info){
			return new StringInfo(sprintf("%g%%", $info->number*100));
		});
	}
}
