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

use function count;
use PHPUnit\Framework\TestCase;
use SOFe\InfoAPI\ParseException;

final class TemplateTest extends TestCase {
	public function testParsePlain() : void {
		$template = Template::parse("lorem ipsum");
		self::assertCount(1, $template->segments);
		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("lorem ipsum", $template->segments[0]->text);
	}

	public function testParseEscapedLeft() : void {
		$template = Template::parse("lorem {{ ipsum");
		self::assertCount(1, $template->segments);
		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("lorem { ipsum", $template->segments[0]->text);
	}

	public function testParseEscapedRight() : void {
		$template = Template::parse("lorem }} ipsum");
		self::assertCount(1, $template->segments);
		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("lorem } ipsum", $template->segments[0]->text);
	}

	public function testParseEscapedOnly() : void {
		$template = Template::parse("{{}}");
		self::assertCount(1, $template->segments);
		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("{}", $template->segments[0]->text);
	}

	public function testParseEscapedContent() : void {
		$template = Template::parse("{{ lorem ipsum }}");
		self::assertCount(1, $template->segments);
		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("{ lorem ipsum }", $template->segments[0]->text);
	}

	public function testParseMismatchingOpenSingleOnly() : void {
		self::expectException(ParseException::class);
		self::expectExceptionMessage("Unmatched open brace. Use {{ to write a literal open brace.");
		self::expectExceptionCode(0);
		Template::parse("{");
	}

	public function testParseMismatchingOpenSingleMiddle() : void {
		self::expectException(ParseException::class);
		self::expectExceptionMessage("Unmatched open brace. Use {{ to write a literal open brace.");
		self::expectExceptionCode(6);
		Template::parse("lorem { ipsum");
	}

	public function testParseMismatchingCloseSingleOnly() : void {
		self::expectException(ParseException::class);
		self::expectExceptionMessage("Unmatched close brace. Use }} to write a literal close brace.");
		self::expectExceptionCode(0);
		Template::parse("}");
	}

	public function testParseMismatchingCloseSingleMiddle() : void {
		self::expectException(ParseException::class);
		self::expectExceptionMessage("Unmatched close brace. Use }} to write a literal close brace.");
		self::expectExceptionCode(6);
		Template::parse("lorem } ipsum");
	}

	public function testParseExprSingle() : void {
		$template = Template::parse("{lorem}");
		self::assertCount(1, $template->segments);
		self::assertInstanceOf(InfoSegment::class, $template->segments[0]);
		self::assertCount(1, $template->segments[0]->head->path->names);
		self::assertSame(["lorem"], $template->segments[0]->head->path->names[0]->getParts());
	}

	public function testParseExprMiddle() : void {
		$template = Template::parse("lorem {ipsum} dolor");
		self::assertCount(3, $template->segments);

		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("lorem ", $template->segments[0]->text);

		self::assertInstanceOf(InfoSegment::class, $template->segments[1]);
		self::assertCount(1, $template->segments[1]->head->path->names);
		self::assertInstanceOf(InfoSegment::class, $template->segments[1]); // phpstan phpunit extension bug workaround
		self::assertSame(["ipsum"], $template->segments[1]->head->path->names[0]->getParts());

		self::assertInstanceOf(TextSegment::class, $template->segments[2]);
		self::assertSame(" dolor", $template->segments[2]->text);
	}

	public function testParseExprWrapped() : void {
		$template = Template::parse("lorem {{{ipsum}}} dolor");
		self::assertCount(3, $template->segments);

		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("lorem {", $template->segments[0]->text);

		self::assertInstanceOf(InfoSegment::class, $template->segments[1]);
		self::assertCount(1, $template->segments[1]->head->path->names);
		self::assertInstanceOf(InfoSegment::class, $template->segments[1]); // phpstan phpunit extension bug workaround
		self::assertSame(["ipsum"], $template->segments[1]->head->path->names[0]->getParts());
		self::assertInstanceOf(InfoSegment::class, $template->segments[1]); // phpstan phpunit extension bug workaround
		self::assertNull($template->segments[1]->head->alternative);

		self::assertInstanceOf(TextSegment::class, $template->segments[2]);
		self::assertSame("} dolor", $template->segments[2]->text);
	}
}
