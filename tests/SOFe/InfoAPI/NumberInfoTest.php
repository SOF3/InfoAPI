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

final class NumberInfoTest extends TestCase {
	public function testOrdinalSt() {
		$api = InfoAPI::createForTesting();

		Defaults::initAll($api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "one", fn($_) => new NumberInfo(1), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "eleven", fn($_) => new NumberInfo(11), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "twentyOne", fn($_) => new NumberInfo(21), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "hundredOne", fn($_) => new NumberInfo(221), $api);

		$actual = InfoAPI::resolve("{one ordinal} {eleven ordinal} {twentyOne ordinal} {hundredOne ordinal}", new Dummy\A(""), false, $api);
		self::assertSame("1st 11th 21st 221st", $actual);
	}

	public function testOrdinalNd() {
		$api = InfoAPI::createForTesting();

		Defaults::initAll($api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "two", fn($_) => new NumberInfo(2), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "twelve", fn($_) => new NumberInfo(12), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "twentyTwo", fn($_) => new NumberInfo(22), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "hundredTwo", fn($_) => new NumberInfo(222), $api);

		$actual = InfoAPI::resolve("{two ordinal} {twelve ordinal} {twentyTwo ordinal} {hundredTwo ordinal}", new Dummy\A(""), false, $api);
		self::assertSame("2nd 12th 22nd 222nd", $actual);
	}

	public function testOrdinalRd() {
		$api = InfoAPI::createForTesting();

		Defaults::initAll($api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "three", fn($_) => new NumberInfo(3), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "thirteen", fn($_) => new NumberInfo(13), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "twentyThree", fn($_) => new NumberInfo(23), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "hundredThree", fn($_) => new NumberInfo(223), $api);

		$actual = InfoAPI::resolve("{three ordinal} {thirteen ordinal} {twentyThree ordinal} {hundredThree ordinal}", new Dummy\A(""), false, $api);
		self::assertSame("3rd 13th 23rd 223rd", $actual);
	}

	public function testOrdinalTh() {
		$api = InfoAPI::createForTesting();

		Defaults::initAll($api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "four", fn($_) => new NumberInfo(4), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "fourteen", fn($_) => new NumberInfo(14), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "twentyFour", fn($_) => new NumberInfo(24), $api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "hundredFour", fn($_) => new NumberInfo(224), $api);

		$actual = InfoAPI::resolve("{four ordinal} {fourteen ordinal} {twentyFour ordinal} {hundredFour ordinal}", new Dummy\A(""), false, $api);
		self::assertSame("4th 14th 24th 224th", $actual);
	}

	public function testPercent() {
		$api = InfoAPI::createForTesting();

		Defaults::initAll($api);
		InfoAPI::provideInfo(Dummy\A::class, NumberInfo::class, "four", fn($_) => new NumberInfo(4), $api);

		$actual = InfoAPI::resolve("{four percent}", new Dummy\A(""), false, $api);
		self::assertSame("4%", $actual);
	}
}
