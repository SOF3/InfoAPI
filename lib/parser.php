<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use Closure;
use Exception;
use RuntimeException;

use function preg_match;
use function str_repeat;
use function str_split;
use function strlen;
use function strpos;
use function substr;

/**
 * @internal Internal parser util.
 */
final class StringParser {
	public int $pos = 0;

	public function __construct(
		private string $buf,
	) {
	}

	public function throwSpan(string $why, ?int $start = null, ?int $end = null, ?int $length = null) : never {
		$start ??= $this->pos;

		if ($end !== null && $length !== null) {
			throw new RuntimeException("\$end and \$length cannot be both specified");
		}

		$end ??= $start + ($length ?? 1);

		throw new ParseException($why, $this->buf, $start, $end);
	}

	public function eof() : bool {
		return strlen($this->buf) === $this->pos;
	}

	public function peek(int $length) : ?string {
		if ($this->pos + $length > strlen($this->buf)) {
			return null;
		}
		return substr($this->buf, $this->pos, $length);
	}

	public function readExactLength(int $length) : ?string {
		if ($this->pos + $length > strlen($this->buf)) {
			return null;
		}
		$ret = substr($this->buf, $this->pos, $length);
		$this->pos += $length;
		return$ret;
	}

	public function readExactText(string $text) : bool {
		if ($this->peek(strlen($text)) === $text) {
			$this->readExactLength(strlen($text));
			return true;
		}

		return false;
	}

	public function readUntil(string ...$needles) : ?string {
		$minPos = null;

		foreach ($needles as $needle) {
			$pos = strpos($this->buf, $needle, $this->pos);
			if ($pos !== false && ($minPos === null || $minPos > $pos)) {
				$minPos = $pos;
			}
		}

		if ($minPos !== null) {
			return $this->readExactLength($minPos - $this->pos);
		}
		return null;
	}

	/**
	 * @param string $regexCharset the contents inside `[]` of a regex.
	 */
	public function readRegexCharset(string $regexCharset) : string {
		return $this->readRegex("[{$regexCharset}]+");
	}

	public function readRegex(string $regex) : string {
		$matched = preg_match("/^{$regex}/", substr($this->buf, $this->pos), $matches);
		if ($matched !== 1) {
			return "";
		}

		return $this->readExactLength(strlen($matches[0]))
			?? throw new RuntimeException("preg_match returned more bytes than available");
	}

	public function skipWhitespace() : void {
		while (!$this->eof()) {
			$replaced = false;
			foreach (str_split(" \t\n\r\v") as $char) {
				if ($this->readExactText($char)) {
					$replaced = true;
					break;
				}
			}

			if (!$replaced) {
				return;
			}
		}
	}

	/**
	 * Try executing something and conditionally roll back the parser if the closure returns false.
	 *
	 * @param Closure(): bool $run
	 */
	public function try(Closure $run) : void {
		$initial = $this->pos;
		if (!$run()) {
			$this->pos = $initial;
		}
	}

	public function peekAll() : string {
		return substr($this->buf, $this->pos);
	}

	public function readAll() : string {
		$ret = substr($this->buf, $this->pos);
		$this->pos = strlen($this->buf);
		return $ret;
	}
}

final class ParseException extends Exception {
	public string $carets;

	public function __construct(
		public string $why,
		public string $buf,
		public int $start,
		public int $end,
	) {
		$carets = str_repeat(" ", $start) . str_repeat("^", $end - $start);
		$this->carets = $carets;
		parent::__construct("$why\n$buf\n$carets");
	}
}
