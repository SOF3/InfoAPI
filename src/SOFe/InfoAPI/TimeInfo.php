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

use function date;
use function fmod;
use function idate;
use function microtime;
use function time;

final class TimeInfo extends Info {
	private int $seconds;
	private int $micros;

	/**
	 * @param int $seconds the Unix timestamp in seconds, e.g. returned by `time()`.
	 * @param int $micros the number of microseconds in the current second,
	 * effectively (but not precisely) `fmod(microtime(true), 1.0) * 1e6`.
	 */
	public function __construct(int $seconds, int $micros = 0) {
		$this->seconds = $seconds;
		$this->micros = $micros;
	}

	static public function fromMicrotime(float $microtime) : self {
		$seconds = (int) $microtime;
		$micros = (int) (fmod($microtime, 1.0) * 1e6);
		return new self($seconds, $micros);
	}

	static public function getInfoType() : string {
		return "time";
	}

	static public function init(?InfoAPI $api) : void {
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.time.year",
			fn($info) => new NumberInfo((float) idate("Y", $info->getSeconds())),
			$api)
			->setMetadata("description", "The year part of a date")
			->setMetadata("example", "2006");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.time.month",
			fn($info) => new NumberInfo((float) idate("m", $info->getSeconds())),
			$api)
			->setMetadata("description", "The month part of a date")
			->setMetadata("example", "1");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.time.date",
			fn($info) => new NumberInfo((float) idate("d", $info->getSeconds())),
			$api)
			->setMetadata("description", "The date part of a date")
			->setMetadata("example", "2");
		InfoAPI::provideInfo(self::class, StringInfo::class, "infoapi.time.weekday",
			fn($info) => new StringInfo(date("D", $info->getSeconds())),
			$api)
			->setMetadata("description", "The weekday part of a date")
			->setMetadata("example", "Thu");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.time.hour",
			fn($info) => new NumberInfo((float) idate("H", $info->getSeconds())),
			$api)
			->setMetadata("description", "The hour part of a time")
			->setMetadata("example", "15");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.time.minute",
			fn($info) => new NumberInfo((float) idate("i", $info->getSeconds())),
			$api)
			->setMetadata("description", "The minute part of a time")
			->setMetadata("example", "4");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.time.second",
			fn($info) => new NumberInfo((float) idate("s", $info->getSeconds())),
			$api)
			->setMetadata("description", "The second part of a time")
			->setMetadata("example", "5");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.time.micro",
			fn($info) => new NumberInfo((float) $info->getMicros()),
			$api)
			->setMetadata("description", "The microsecond part of a time")
			->setMetadata("example", "0");

		InfoAPI::provideInfo(self::class, DurationInfo::class, "infoapi.time.elapsed",
			fn($info) => new DurationInfo(microtime(true) - $info->asMicrotime()), $api);
		InfoAPI::provideInfo(self::class, DurationInfo::class, "infoapi.time.remaining",
			fn($info) => new DurationInfo($info->asMicrotime() - microtime(true)), $api);

		InfoAPI::provideInfo(CommonInfo::class, self::class, "infoapi.time.now",
			fn($_) => new TimeInfo(time()), $api);
	}

	public function getSeconds() : int {
		return $this->seconds;
	}

	public function getMicros() : int {
		return $this->micros;
	}

	/**
	 * Approximate the timestamp as a float like in `microtime(true)` format.
	 * The value should be precise up to microseconds on 64-bit systems.
	 */
	public function asMicrotime() : float {
		$ret = (float) $this->seconds;
		$ret += $this->micros * 1e-6;
		return $ret;
	}

	public function toString() : string {
		return date("Y-m-d H:i:s", $this->getSeconds());
	}
}
