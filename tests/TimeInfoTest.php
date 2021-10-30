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

use PHPUnit\Framework\TestCase;
use function fmod;
use function microtime;
use function usleep;

final class TimeInfoTest extends TestCase {
	private const MAGIC_TIMESTAMP = 1136214245; // 2006-01-02 15:04:05 UTC, 2006-01-02 23:04:05 Asia/Singapore, Monday
	private const MAGIC_TIMESTAMP_MICROS = 6;
	private const MAGIC_TIMEZONE = "Asia/Singapore"; // UTC+8, no DST

	static private function implTest(string $expected, string $template) : void {
		$api = InfoAPI::createForTesting();

		Defaults::initAll($api);
		InfoAPI::provideInfo(Dummy\A::class, TimeInfo::class, "test",
			fn($_) => new TimeInfo(self::MAGIC_TIMESTAMP, self::MAGIC_TIMESTAMP_MICROS),
			$api);

		date_default_timezone_set(self::MAGIC_TIMEZONE);

		$actual = InfoAPI::resolve($template, new Dummy\A(""), false, $api);

		self::assertSame($expected, $actual);
	}

	public function testYmd() : void {
		self::implTest("2006 1 2", "{test year} {test month} {test date}");
	}

	public function testHms() : void {
		self::implTest("23 4 5", "{test hour} {test minute} {test second}");
	}

	public function testWeekday() : void {
		self::implTest("Mon", "{test weekday}");
	}

	public function testMicros() : void {
		self::implTest("6", "{test micro}");
	}

	public function testToString() : void {
		self::implTest("2006-01-02 23:04:05", "{test}");
	}

	static public function waitToMidSecond() : void {
		$now = microtime(true);
		$fract = 1.0 - fmod($now + 0.5, 1.0);
		usleep((int) ($fract * 1e6));
	}
}
