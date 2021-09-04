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

use function abs;
use function sprintf;

final class NumberInfo extends Info {
	private float $value;

	public function __construct(float $value) {
		$this->value = $value;
	}

	public function getValue() : float {
		return $this->value;
	}

	public function toString() : string {
		return sprintf("%g", $this->value);
	}

	public function getInfoType() : string {
		return "number";
	}

	static public function init() : void {
		InfoAPI::provideInfo(self::class, StringInfo::class, "infoapi.number.ordinal",
			static function(NumberInfo $info) : ?StringInfo {
				$int = (int) $info->value;
				if($info->value !== (float) $int){
					return null;
				}
				$int = abs($int) % 100;
				if($int !== 11 && $int % 10 === 1){
					$suffix = "st";
				}elseif($int !== 12 && $int % 10 === 2){
					$suffix = "nd";
				}elseif($int !== 13 && $int % 10 === 3){
					$suffix = "rd";
				}else{
					$suffix = "th";
				}
				return new StringInfo($int . $suffix);
			});
		InfoAPI::provideInfo(self::class, StringInfo::class, "infoapi.number.percent",
			fn($info) => new StringInfo(sprintf("%g%%", $info->value)));
	}
}
