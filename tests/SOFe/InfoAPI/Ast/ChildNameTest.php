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

namespace SOFe\InfoAPI\Ast;

use PHPUnit\Framework\TestCase;

final class ChildNameTest extends TestCase {
	public function testChildNameMatchExact() {
		self::assertChildNameMatch("a", "a", true);
		self::assertChildNameMatch("a.b.c", "a.b.c", true);
	}

	public function testChildNameMatchSuffix() {
		self::assertChildNameMatch("a.b", "b", true);
		self::assertChildNameMatch("a.b.c", "b.c", true);
	}

	public function testChildNameMatchSubsequence() {
		self::assertChildNameMatch("a.b.c", "a.c", true);
	}

	public function testChildNameMismatchDifferentSuffix() {
		self::assertChildNameMatch("a", "b", false);
		self::assertChildNameMatch("a.b", "a", false);
		self::assertChildNameMatch("a.b.d", "a.b.c", false);
		self::assertChildNameMatch("a.b.c.d", "a.b.c", false);
		self::assertChildNameMatch("a.c.b", "a.b.c", false);
	}

	public function testChildNameMismatchMissingPart() {
		self::assertChildNameMatch("a.b", "a.b.c", false);
		self::assertChildNameMatch("a.c", "a.b.c", false);
		self::assertChildNameMatch("b.c", "a.b.c", false);
	}

	public function testChildNameMismatchOrder() {
		self::assertChildNameMatch("a.b", "b.a", false);
		self::assertChildNameMatch("a.c.b", "a.b.c", false);
		self::assertChildNameMatch("b.a.c", "a.b.c", false);
		self::assertChildNameMatch("b.c.a", "a.b.c", false);
		self::assertChildNameMatch("c.a.b", "a.b.c", false);
		self::assertChildNameMatch("c.b.a", "a.b.c", false);
	}

	static private function assertChildNameMatch(string $fullString, string $subString, bool $expect) {
		$full = ChildName::parse($fullString);
		$sub = ChildName::parse($subString);
		self::assertSame($expect, $full->matches($sub));
	}
}
