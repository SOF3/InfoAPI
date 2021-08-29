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
	public function testParsePlain() {
		$template = Template::parse("lorem ipsum");
		self::assertSame(1, count($template->segments));
		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("lorem ipsum", $template->segments[0]->text);
	}

	public function testParseEscapedLeft() {
		$template = Template::parse("lorem {{ ipsum");
		self::assertSame(1, count($template->segments));
		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("lorem { ipsum", $template->segments[0]->text);
	}

	public function testParseEscapedRight() {
		$template = Template::parse("lorem }} ipsum");
		self::assertSame(1, count($template->segments));
		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("lorem } ipsum", $template->segments[0]->text);
	}

	public function testParseEscapedOnly() {
		$template = Template::parse("{{}}");
		self::assertSame(1, count($template->segments));
		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("{}", $template->segments[0]->text);
	}

	public function testParseEscapedContent() {
		$template = Template::parse("{{ lorem ipsum }}");
		self::assertSame(1, count($template->segments));
		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("{ lorem ipsum }", $template->segments[0]->text);
	}

	public function testParseMismatchingOpenSingleOnly() {
		self::expectException(ParseException::class);
		self::expectExceptionMessage("Unmatched open brace. Use {{ to write a literal open brace.");
		self::expectExceptionCode(0);
		Template::parse("{");
	}

	public function testParseMismatchingOpenSingleMiddle() {
		self::expectException(ParseException::class);
		self::expectExceptionMessage("Unmatched open brace. Use {{ to write a literal open brace.");
		self::expectExceptionCode(6);
		Template::parse("lorem { ipsum");
	}

	public function testParseMismatchingCloseSingleOnly() {
		self::expectException(ParseException::class);
		self::expectExceptionMessage("Unmatched close brace. Use }} to write a literal close brace.");
		self::expectExceptionCode(0);
		Template::parse("}");
	}

	public function testParseMismatchingCloseSingleMiddle() {
		self::expectException(ParseException::class);
		self::expectExceptionMessage("Unmatched close brace. Use }} to write a literal close brace.");
		self::expectExceptionCode(6);
		Template::parse("lorem } ipsum");
	}

	public function testParseExprSingle() {
		$template = Template::parse("{lorem}");
		self::assertSame(1, count($template->segments));
		self::assertInstanceOf(InfoSegment::class, $template->segments[0]);
		self::assertSame(1, count($template->segments[0]->head->path->names));
		self::assertSame(["lorem"], $template->segments[0]->head->path->names[0]->getParts());
	}

	public function testParseExprMiddle() {
		$template = Template::parse("lorem {ipsum} dolor");
		self::assertSame(3, count($template->segments));

		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("lorem ", $template->segments[0]->text);

		self::assertInstanceOf(InfoSegment::class, $template->segments[1]);
		self::assertSame(1, count($template->segments[1]->head->path->names));
		self::assertSame(["ipsum"], $template->segments[1]->head->path->names[0]->getParts());

		self::assertInstanceOf(TextSegment::class, $template->segments[2]);
		self::assertSame(" dolor", $template->segments[2]->text);
	}

	public function testParseExprWrapped() {
		$template = Template::parse("lorem {{{ipsum}}} dolor");
		self::assertSame(3, count($template->segments));

		self::assertInstanceOf(TextSegment::class, $template->segments[0]);
		self::assertSame("lorem {", $template->segments[0]->text);

		self::assertInstanceOf(InfoSegment::class, $template->segments[1]);
		self::assertSame(1, count($template->segments[1]->head->path->names));
		self::assertSame(["ipsum"], $template->segments[1]->head->path->names[0]->getParts());
		self::assertNull($template->segments[1]->head->alternative);

		self::assertInstanceOf(TextSegment::class, $template->segments[2]);
		self::assertSame("} dolor", $template->segments[2]->text);
	}
}
