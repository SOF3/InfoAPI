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
use SOFe\InfoAPI\ParseException;
use function count;

final class InfoSegmentTest extends TestCase {
	public function testParseOneExpr() : void {
		$segment = InfoSegment::parse("a.b c.d", 0);
		self::assertCount(2, $segment->head->path->names);
		self::assertSame(["a", "b"], $segment->head->path->names[0]->getParts());
		self::assertSame(["c", "d"], $segment->head->path->names[1]->getParts());
		self::assertNull($segment->head->alternative);
	}

	public function testParseTwoExpr() : void {
		$segment = InfoSegment::parse("a.b c.d | e.f g.h", 0);

		self::assertCount(2, $segment->head->path->names);
		self::assertSame(["a", "b"], $segment->head->path->names[0]->getParts());
		self::assertSame(["c", "d"], $segment->head->path->names[1]->getParts());
		self::assertNotNull($segment->head->alternative);

		self::assertCount(2, $segment->head->alternative->path->names);
		self::assertSame(["e", "f"], $segment->head->alternative->path->names[0]->getParts());
		self::assertSame(["g", "h"], $segment->head->alternative->path->names[1]->getParts());
		self::assertNull($segment->head->alternative->alternative);
	}

	public function testParseTwoExprUntrimmed() : void {
		$segment = InfoSegment::parse("  a.b c.d  |  e.f  g.h ", 0);

		self::assertCount(2, $segment->head->path->names);
		self::assertSame(["a", "b"], $segment->head->path->names[0]->getParts());
		self::assertSame(["c", "d"], $segment->head->path->names[1]->getParts());
		self::assertNotNull($segment->head->alternative);

		self::assertCount(2, $segment->head->alternative->path->names);
		self::assertSame(["e", "f"], $segment->head->alternative->path->names[0]->getParts());
		self::assertSame(["g", "h"], $segment->head->alternative->path->names[1]->getParts());
		self::assertNull($segment->head->alternative->alternative);
	}

	public function testParseEmptySegment() : void {
		self::expectException(ParseException::class);
		self::expectExceptionMessage("Empty template or coalescence path");
		self::expectExceptionCode(10);
		InfoSegment::parse("", 10); // we start with 10 here to ensure InfoSegment adds the base index
	}

	public function testParseSpacedSegment() : void {
		self::expectException(ParseException::class);
		self::expectExceptionMessage("Empty template or coalescence path");
		self::expectExceptionCode(10);
		InfoSegment::parse("   ", 10); // we start with 10 here to ensure InfoSegment adds the base index
	}

	public function testParseEmptyCoalescence() : void {
		self::expectException(ParseException::class);
		self::expectExceptionMessage("Empty template or coalescence path");
		self::expectExceptionCode(14);
		InfoSegment::parse(" a | | b  ", 10); // we start with 10 here to ensure InfoSegment adds the base index
	}
}
