<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Ast;

use PHPUnit\Framework\TestCase;
use SOFe\InfoAPI\StringParser;
use function ltrim;
use function strstr;

final class AstTest extends TestCase {
	public function testParseNamedTrueArg() : void {
		$parser = new StringParser("arg=true, xxx");
		$arg = Parse::parseArg($parser);
		self::assertSame("arg", $arg->name);
		self::assertInstanceOf(JsonValue::class, $arg->value);
		self::assertSame("true", $arg->value->asString);
		self::assertSame(", xxx", $parser->peekAll());
	}

	public function testParseNamedFalseArg() : void {
		$parser = new StringParser("arg=false) xxx");
		$arg = Parse::parseArg($parser);
		self::assertSame("arg", $arg->name);
		self::assertInstanceOf(JsonValue::class, $arg->value);
		self::assertSame("false", $arg->value->asString);
		self::assertSame(") xxx", $parser->peekAll());
	}

	public function testParseUnnamedTrueArg() : void {
		$parser = new StringParser("true) xxx");
		$arg = Parse::parseArg($parser);
		self::assertNull($arg->name);
		self::assertInstanceOf(JsonValue::class, $arg->value);
		self::assertSame("true", $arg->value->asString);
		self::assertSame(") xxx", $parser->peekAll());
	}

	public function testParseUnnamedFalseArg() : void {
		$parser = new StringParser("false, xxx");
		$arg = Parse::parseArg($parser);
		self::assertNull($arg->name);
		self::assertInstanceOf(JsonValue::class, $arg->value);
		self::assertSame("false", $arg->value->asString);
		self::assertSame(", xxx", $parser->peekAll());
	}

	public function testParseUnnamedNumericArg() : void {
		$parser = new StringParser("-1.23e+45, xxx");
		$arg = Parse::parseArg($parser);
		self::assertNull($arg->name);
		self::assertInstanceOf(JsonValue::class, $arg->value);
		self::assertSame("-1.23e+45", $arg->value->asString);
		self::assertSame(", xxx", $parser->peekAll());
	}

	public function testParseUnnamedIntegerArg() : void {
		// ensure that `123` doesn't get treated as an arg name.
		$parser = new StringParser("123, xxx");
		$arg = Parse::parseArg($parser);
		self::assertNull($arg->name);
		self::assertInstanceOf(JsonValue::class, $arg->value);
		self::assertSame("123", $arg->value->asString);
		self::assertSame(", xxx", $parser->peekAll());
	}

	public function testParseUnnamedStringArg() : void {
		$input = <<<'EOS'
			"a\"b\n\\", xxx
			EOS;
		$parser = new StringParser($input);
		$arg = Parse::parseArg($parser);
		self::assertNull($arg->name);
		self::assertInstanceOf(JsonValue::class, $arg->value);
		self::assertSame("a\"b\n\\", $arg->value->asString);
		self::assertSame(strstr($input, ",", true), $arg->value->json);
		self::assertSame(", xxx", $parser->peekAll());
	}

	public function testParseCallWithoutArgs() : void {
		$parser = new StringParser("foo:bar qux");
		$call = Parse::parseCall($parser);
		self::assertSame(["foo", "bar"], $call->name->tokens);
		self::assertNull($call->args);
		self::assertSame("qux", ltrim($parser->peekAll()));
	}

	public function testParseCallWithEmptyArgs() : void {
		$parser = new StringParser("foo:bar() qux");
		$call = Parse::parseCall($parser);
		self::assertSame(["foo", "bar"], $call->name->tokens);
		self::assertSame([], $call->args);
		self::assertSame("qux", ltrim($parser->peekAll()));
	}

	public function testParseCallWithNamedArgs() : void {
		$parser = new StringParser('foo:bar(corge = true, grault = 123e1, baz = "text", waldo = false) qux');
		$call = Parse::parseCall($parser);
		self::assertSame(["foo", "bar"], $call->name->tokens);
		self::assertNotNull($call->args);
		self::assertCount(4, $call->args);

		self::assertSame("corge", $call->args[0]->name);
		self::assertInstanceOf(JsonValue::class, $call->args[0]->value);
		self::assertSame("true", $call->args[0]->value->json);

		self::assertSame("grault", $call->args[1]->name);
		self::assertInstanceOf(JsonValue::class, $call->args[1]->value);
		self::assertSame("123e1", $call->args[1]->value->json);

		self::assertSame("baz", $call->args[2]->name);
		self::assertInstanceOf(JsonValue::class, $call->args[2]->value);
		self::assertSame("text", $call->args[2]->value->asString);

		self::assertSame("waldo", $call->args[3]->name);
		self::assertInstanceOf(JsonValue::class, $call->args[3]->value);
		self::assertSame("false", $call->args[3]->value->json);

		self::assertSame("qux", ltrim($parser->peekAll()));
	}

	public function testParseCallWithUnnamedArgs() : void {
		$parser = new StringParser('foo:bar(true, 123e1, "text", false) qux');
		$call = Parse::parseCall($parser);
		self::assertSame(["foo", "bar"], $call->name->tokens);
		self::assertNotNull($call->args);
		self::assertCount(4, $call->args);

		self::assertNull($call->args[0]->name);
		self::assertInstanceOf(JsonValue::class, $call->args[0]->value);
		self::assertSame("true", $call->args[0]->value->json);

		self::assertNull($call->args[1]->name);
		self::assertInstanceOf(JsonValue::class, $call->args[1]->value);
		self::assertSame("123e1", $call->args[1]->value->json);

		self::assertNull($call->args[2]->name);
		self::assertInstanceOf(JsonValue::class, $call->args[2]->value);
		self::assertSame("text", $call->args[2]->value->asString);

		self::assertNull($call->args[3]->name);
		self::assertInstanceOf(JsonValue::class, $call->args[3]->value);
		self::assertSame("false", $call->args[3]->value->json);

		self::assertSame("qux", ltrim($parser->peekAll()));
	}

	public function testParseInfoExprWithoutArgs() : void {
		$parser = new StringParser("foo:bar qux |");
		$expr = Parse::parseInfoExpr($parser, null, "|", "}");
		self::assertNotNull($expr->parent);
		self::assertSame(["foo", "bar"], $expr->parent->call->name->tokens);
		self::assertSame(["qux"], $expr->call->name->tokens);
	}

	public function testParseInfoExprWithEmpty() : void {
		$parser = new StringParser("foo:bar() qux}");
		$expr = Parse::parseInfoExpr($parser, null, "|", "}");
		self::assertNotNull($expr->parent);
		self::assertSame(["foo", "bar"], $expr->parent->call->name->tokens);
		self::assertSame(["qux"], $expr->call->name->tokens);
	}

	public function testParseExpr() : void {
		$parser = new StringParser("foo:bar() qux | corge grault() }");
		$expr = Parse::parseExpr($parser, "}");
		self::assertNotNull($expr->main->parent);
		self::assertNull($expr->main->parent->parent);
		self::assertSame(["foo", "bar"], $expr->main->parent->call->name->tokens);
		self::assertSame(["qux"], $expr->main->call->name->tokens);
		self::assertNotNull($expr->else);
		self::assertNotNull($expr->else->main->parent);
		self::assertNull($expr->else->main->parent->parent);
		self::assertSame(["corge"], $expr->else->main->parent->call->name->tokens);
		self::assertSame(["grault"], $expr->else->main->call->name->tokens);
	}
}
