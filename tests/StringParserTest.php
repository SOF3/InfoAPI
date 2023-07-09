<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use PHPUnit\Framework\TestCase;

final class StringParserTest extends TestCase {
	public function testPeek() : void {
		$parser = new StringParser("abc");

		self::assertSame("a", $parser->readExactLength(1));

		self::assertSame("b", $parser->peek(1));
		self::assertSame("bc", $parser->peek(2));

		self::assertNull($parser->peek(3));
	}

	public function testReadExactText() : void {
		$parser = new StringParser("abc");

		self::assertTrue($parser->readExactText("a"));
		self::assertFalse($parser->readExactText("a"));
		self::assertFalse($parser->readExactText("bcd"));
		self::assertTrue($parser->readExactText("bc"));
		self::assertFalse($parser->readExactText("d"));
	}

	public function testReadUntil() : void {
		$parser = new StringParser("abcdabcdabcd");
		self::assertSame("", $parser->readUntil("a"));
		self::assertSame("a", $parser->readUntil("b"));
		self::assertSame("bc", $parser->readUntil("d", "a"));
		self::assertSame("d", $parser->readUntil("b", "a"));
	}

	public function testSkipWhitespace() : void {
		$parser = new StringParser("a   b\t \nc");
		$parser->skipWhitespace();
		self::assertSame(0, $parser->pos);
		self::assertTrue($parser->readExactText("a"));
		$parser->skipWhitespace();
		self::assertSame(4, $parser->pos);
		self::assertTrue($parser->readExactText("b"));
		$parser->skipWhitespace();
		self::assertSame(8, $parser->pos);
		self::assertTrue($parser->readExactText("c"));
		$parser->skipWhitespace();
		self::assertSame(9, $parser->pos);
	}
}
