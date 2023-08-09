<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\KindMeta;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\ReflectUtil;
use function is_bool;

final class Bools {
	public static function register(Indices $indices) : void {
		$indices->registries->kindMetas->register(new KindMeta(Standard\BoolInfo::KIND, "Boolean", "A condition that is either true or false", []));
		$indices->registries->displays->register(new Display(Standard\BoolInfo::KIND, fn($value) => is_bool($value) ? ($value ? "true" : "false") : Display::INVALID));

		ReflectUtil::addClosureMapping(
			$indices, "infoapi:bool", ["if"], fn(bool $value, string $then, string $else) : string => $value ? $then : $else,
			help: "Resolve to the first argument (\"then\") if the condition is true, otherwise to the second arugment (\"else\").",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:bool", ["and"], fn(bool $v1, bool $v2) : bool => $v1 && $v2,
			help: "Check if both conditions are true",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:bool", ["or"], fn(bool $v1, bool $v2) : bool => $v1 || $v2,
			help: "Check if either condition is true",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:bool", ["xor"], fn(bool $v1, bool $v2) : bool => $v1 !== $v2,
			help: "Check if exactly one of the conditions is true",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:bool", ["not"], fn(bool $value) : bool => !$value,
			help: "Negate the condition",
		);
	}
}
