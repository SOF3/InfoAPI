<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Ast;

use JsonException;
use Shared\SOFe\InfoAPI\Mapping;
use SOFe\InfoAPI\QualifiedRef;
use SOFe\InfoAPI\StringParser;
use function is_numeric;
use function is_string;
use function json_decode;
use function strlen;

/** The entire template string. */
final class Template {
	public function __construct(
		/** @var (RawText|Expr)[] */
		public array $elements,
	) {
	}
}

/** Raw text that can be printed as-is. */
final class RawText {
	public function __construct(
		/** The parsed string to be printed, e.g. `a` in `{{ a | b | c }}`. */
		public string $parsed,
		/** The original string that contains escape sequences, e.g. `b | c` in `{{ a | b | c }}`. */
		public string $original,
	) {
	}
}

/** An expression that may have a coalescence chain. */
final class Expr {
	public function __construct(
		/** The main expression to resolve. */
		public InfoExpr $main,
		/** The expression to use if the main expression is null or does not have a display descriptor. */
		public ?Expr $else,
	) {
	}
}

/** An expression that resolves info. */
final class InfoExpr {
	public function __construct(
		/** The parent part, e.g. `a b` in `{{ a b c }}`. */
		public ?InfoExpr $parent,

		/** The part that calls a mapping on the parent, e.g. `c(...)` in `{{ a b c(...) }}`. */
		public MappingCall $call,
	) {
	}
}

/** Calls a mapping with or without arguments. */
final class MappingCall {
	public function __construct(
		/** The partially qualified reference to the mapping, e.g. `a:b` in `{{ a:b(c, d=e) }}`. */
		public QualifiedRef $name,
		/** @var Arg[]|null The arguments passed to the mapping, e.g. `c` and `d=e` in `{{ a:b(c, d=e) }}` */
		public ?array $args,
	) {
	}
}

/** An argument passed to a mapping. */
final class Arg {
	public function __construct(
		/** Name of the argument if specified, e.g. `d` in `{{ a:b(c, d=e) }}`. */
		public ?string $name,

		/**
		 * The value of the argument.
		 * Parses as an Expr if it starts with an identifier (except `true` and `false`),
		 * otherwise parses as one JSON expression. */
		public JsonValue|Expr $value,
	) {
	}
}

/** A value in JSON format to be interpreted based on the type. */
final class JsonValue {
	public function __construct(
		public string $asString,
		public string $json,
	) {
	}
}

final class Parse {
	public static function parse(string $buf) : Template {
		$parser = new StringParser($buf);

		$elements = [];

		while (!$parser->eof()) {
			$elements[] = self::parseElement($parser);
		}

		return new Template($elements);
	}

	public static function parseElement(StringParser $parser) : Expr|RawText {
		if ($parser->readExactText("{{")) {
			return new RawText("{", "{{");
		}
		if ($parser->readExactText("}}")) {
			return new RawText("{", "{{");
		}
		if ($parser->readExactText("}")) {
			throw $parser->throwSpan("unmatched `}` should be escaped as `}}`");
		}

		$startExprPos = $parser->pos;
		if ($parser->readExactText("{")) {
			$expr = self::parseExpr($parser, "}");
			$parser->skipWhitespace();
			if (!$parser->readExactText("}")) {
				throw $parser->throwSpan("unclosed `{}` or invalid character in expression", start: $startExprPos, end: $parser->pos + 1);
			}
			return $expr;
		}

		$substr = $parser->readUntil("{", "}") ?? $parser->readAll();
		return new RawText($substr, $substr);
	}

	public static function parseExpr(StringParser $parser, string ...$terminators) : Expr {
		$main = self::parseInfoExpr($parser, null, "|", ...$terminators);
		$parser->skipWhitespace();

		$else = null;
		if ($parser->readExactText("|")) {
			$else = self::parseExpr($parser, ...$terminators);
		}

		return new Expr($main, $else);
	}

	public static function parseInfoExpr(StringParser $parser, ?InfoExpr $parent, string ...$terminators) : InfoExpr {
		$call = self::parseCall($parser);
		$expr = new InfoExpr($parent, $call);

		$parser->skipWhitespace();
		foreach ($terminators as $terminator) {
			if ($parser->peek(strlen($terminator)) === $terminator) {
				return $expr;
			}
		}

		return self::parseInfoExpr($parser, $expr, ...$terminators);
	}

	public static function parseCall(StringParser $parser) : MappingCall {
		$name = self::parseName($parser);

		$args = null;
		$parser->skipWhitespace();
		$startArgsPos = $parser->pos;
		if ($parser->readExactText("(")) {
			$args = [];
			while (!$parser->readExactText(")")) {
				$args[] = self::parseArg($parser);
				if (!$parser->readExactText(",")) {
					if (!$parser->readExactText(")")) {
						throw $parser->throwSpan("multiple arguments must be separated by `,` or terminated with `)`", start: $startArgsPos, end: $parser->pos);
					}
					break;
				}
			}
		}
		return new MappingCall($name, $args);
	}

	public static function parseName(StringParser $parser) : QualifiedRef {
		$tokens = [];
		$parser->skipWhitespace();

		do {
			$token = $parser->readRegexCharset(Mapping::FQN_TOKEN_REGEX_CHARSET);
			if (strlen($token) === 0) {
				throw $parser->throwSpan("expected mapping name");
			}
			$tokens[] = $token;
			$hasMore = $parser->readExactText(Mapping::FQN_SEPARATOR);
		} while ($hasMore);

		return new QualifiedRef($tokens);
	}

	public static function parseArg(StringParser $parser) : Arg {
		$parser->skipWhitespace();

		if ($parser->readExactText("true")) {
			return new Arg(null, new JsonValue("true", "true"));
		}
		if ($parser->readExactText("false")) {
			return new Arg(null, new JsonValue("false", "false"));
		}

		$argName = null;

		$parser->try(function() use ($parser, &$argName) : bool {
			$nameToken = $parser->readRegexCharset(Mapping::FQN_TOKEN_REGEX_CHARSET);
			$parser->skipWhitespace();

			if ($parser->readExactText("=")) {
				$argName = $nameToken;
				return true;
			}
			return false;
		});

		return new Arg($argName, self::parseValue($parser));
	}

	private const JSON_STRING_REGEX = <<<'EOS'
		"(?>\\(?>["\\\/bfnrt]|u[a-fA-F0-9]{4})|[^"\\\0-\x1F\x7F]+)*"
		EOS;

	public static function parseValue(StringParser $parser) : JsonValue|Expr {
		$parser->skipWhitespace();
		$startPos = $parser->pos;

		if ($parser->readExactText("true")) {
			return new JsonValue("true", "true");
		}
		if ($parser->readExactText("false")) {
			return new JsonValue("false", "false");
		}

		$num = $parser->readRegexCharset('0-9e\.\-\+');
		if (is_numeric($num)) {
			return new JsonValue($num, $num);
		}

		if ($parser->peek(1) === '"') {
			// Source: https://stackoverflow.com/a/32155765/3990767
			$string = $parser->readRegex(self::JSON_STRING_REGEX);
			if ($string === "") {
				throw $parser->throwSpan("expected JSON string");
			}
			try {
				$parsed = json_decode($string, false, 1, JSON_THROW_ON_ERROR);
				if (!is_string($parsed)) {
					throw $parser->throwSpan("expected JSON string", start: $startPos, length: strlen($string));
				}

				return new JsonValue(asString: $parsed, json: $string);
			} catch(JsonException $e) {
				throw $parser->throwSpan("JSON parse error: {$e->getMessage()}", start: $startPos, length: strlen($string));
			}
		}

		return self::parseExpr($parser, ",", ")");
	}
}
