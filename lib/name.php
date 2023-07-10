<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use Shared\SOFe\InfoAPI\Mapping;
use function array_shift;
use function count;
use function explode;
use function implode;

final class FullyQualifiedName {
	/**
	 * @param string[] $tokens
	 */
	public function __construct(
		public array $tokens,
	) {
	}

	public function toString() : string {
		return implode(Mapping::FQN_SEPARATOR, $this->tokens);
	}

	public function shortName() : string {
		return $this->tokens[count($this->tokens) - 1];
	}

	public static function parse(string $text) : self {
		return new self(explode(Mapping::FQN_SEPARATOR, $text));
	}

	/**
	 * Tests if the input matches this FQN.
	 * Returns null if it does not match.
	 * Returns 0 if it is an exact match.
	 * Returns a positive number that indicates the number of missing tokens if it is a fuzzy match.
	 */
	public function match(QualifiedRef $ref) : ?int {
		$input = $ref->tokens;
		if ($input[count($input) - 1] !== $this->tokens[count($this->tokens) - 1]) {
			return null;
		}

		$missing = 0;
		foreach ($this->tokens as $token) {
			if ($token === $input[0]) {
				array_shift($input);
			} else {
				$missing += 1;
			}
		}

		if (count($input) > 0) {
			// $input[0] does not match any tokens in this FQN.
			return null;
		}

		return $missing;
	}
}

final class QualifiedRef {
	/**
	 * @param string[] $tokens
	 */
	public function __construct(
		public array $tokens,
	) {
	}

	public function shortName() : string {
		return $this->tokens[count($this->tokens) - 1];
	}

	public static function parse(string $text) : self {
		return new self(explode(Mapping::FQN_SEPARATOR, $text));
	}
}
