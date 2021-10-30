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
use function floor;
use function fmod;
use function ucfirst;

final class DurationInfo extends Info {
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
		foreach([
			"days" => [INF, 86400.0],
			"hours" => [86400.0, 3600.0],
			"minutes" => [3600.0, 60.0],
			"seconds" => [60.0, 1.0],
		] as $name => [$modSuper, $mod]) {
			InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.duration.$name",
				fn($info) => new NumberInfo(self::rounded($info->getValue(), $mod, $modSuper)), $api);
			InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.duration.raw" . ucfirst($name),
				fn($info) => new NumberInfo($info->getValue() / $mod), $api);
		}
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
		// TODO
	}
}
