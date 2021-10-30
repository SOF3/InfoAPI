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

final class DurationInfoTest extends TestCase {
	static private function implTest(string $expected, string $template, float $duration) : void {
		$api = InfoAPI::createForTesting();

		Defaults::initAll($api);
		InfoAPI::provideInfo(Dummy\A::class, DurationInfo::class, "test", fn($_) => new DurationInfo($duration), $api);

		$actual = InfoAPI::resolve($template, new Dummy\A(""), false, $api);
		self::assertSame($expected, $actual);
	}

	public function testDays() {
		self::implTest("3", "{test days}", 86400.0 * 3.8);
	}

	public function testRawDays() {
		// note to future changes: ensure that the multiplied value is exact under IEEE754/binary64
		self::implTest("3.8", "{test rawDays}", 86400.0 * 3.8);
	}

	public function testHours() {
		self::implTest("3", "{test hours}", 3600.0 * 3.8);
	}

	public function testRawHours() {
		// note to future changes: ensure that the multiplied value is exact under IEEE754/binary64
		self::implTest("3.8", "{test rawHours}", 3600.0 * 3.8);
	}

	public function testMinutes() {
		self::implTest("3", "{test minutes}", 60.0 * 3.8);
	}

	public function testRawMinutes() {
		// note to future changes: ensure that the multiplied value is exact under IEEE754/binary64
		self::implTest("3.8", "{test rawMinutes}", 60.0 * 3.8);
	}

	public function testSeconds() {
		self::implTest("3", "{test seconds}", 3.8);
	}

	public function testRawSeconds() {
		// note to future changes: ensure that the multiplied value is exact under IEEE754/binary64
		self::implTest("3.8", "{test rawSeconds}", 3.8);
	}
}
