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

final class FallbackTest extends TestCase {
	public function testFallback() : void {
		$api = InfoAPI::createForTesting();
		InfoAPI::provideInfo(Dummy\B::class, Dummy\C::class, "b2c", fn($b) => new Dummy\C($b->toString()), $api);
		InfoAPI::provideFallback(Dummy\A::class, Dummy\B::class, fn($a) => new Dummy\B($a->toString()), $api);

		$actual = InfoAPI::resolve("{b2c}", new Dummy\A("x"), false, $api);
		$this->assertEquals("x", $actual);
	}
}
