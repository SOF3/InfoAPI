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

use const INF;
use function count;
use function floor;
use function fmod;
use function implode;
use function microtime;
use function ucfirst;

final class DurationInfo extends Info {
	private const UNITS = [
		"days" => 86400.0,
		"hours" => 3600.0,
		"minutes" => 60.0,
		"seconds" => 1.0,
	];

	private float $value;

	/**
	 * @param float $value the duration in number of seconds.
	 */
	public function __construct(float $value) {
		$this->value = $value;
	}

	static public function getInfoType() : string {
		return "duration";
	}

	static public function init(?InfoAPI $api) : void {
		$nameLast = null;
		$modLast = INF;

		foreach(self::UNITS as $name => $mod) {
			$exclude = $nameLast !== null ? " excluding whole $nameLast" : "";
			InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.duration.$name",
				fn($info) => new NumberInfo(self::rounded($info->getValue(), $mod, $modLast)),
				$api)
				->setMetadata("description", "Number of $name" . $exclude)
				->setMetadata("example", "3");
			InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.duration.raw" . ucfirst($name),
				fn($info) => new NumberInfo($info->getValue() / $mod),
				$api)
				->setMetadata("description", "Number of $name")
				->setMetadata("example", "3.5");

			$nameLast = $name;
			$modLast = $mod;
		}

		InfoAPI::provideInfo(self::class, TimeInfo::class, "infoapi.duration.later",
			fn($info) => TimeInfo::fromMicrotime(microtime(true) + $info->getValue()),
			$api)
			->setMetadata("description", "The time for this duration after now");
		InfoAPI::provideInfo(self::class, TimeInfo::class, "infoapi.duration.ago",
			fn($info) => TimeInfo::fromMicrotime(microtime(true) - $info->getValue()));
	}

	static private function rounded(float $value, float $mod, float $modSuper) : float {
		$value = fmod($value, $modSuper);
		return floor($value / $mod);
	}

	/**
	 * Returns the duration in number of seconds.
	 */
	public function getValue() : float {
		return $this->value;
	}

	public function toString() : string {
		$output = [];

		$modLast = INF;

		$value = $this->getValue();

		$negative = $value < 0;
		if($negative) {
			$value *= -1.0;
		}

		foreach(self::UNITS as $name => $mod) {
			$rounded = self::rounded($value, $mod, $modLast);
			if($rounded > 0.0) {
				$output[] = "$rounded $name";
			}

			$modLast = $mod;
		}

		if(count($output) === 0) {
			return "immediately";
		}

		$negativeSign = $negative ? "-" : "";
		return $negativeSign . implode(" ", $output);
	}
}
