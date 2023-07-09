<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Registry;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\ReflectHintIndex;
use SOFe\InfoAPI\ReflectUtil;
use function is_bool;

final class Bools {
	/**
	 * @param Registry<Display> $displays
	 * @param Registry<Mapping> $mappings
	 */
	public static function register(Registry $displays, Registry $mappings, ReflectHintIndex $hints) : void {
		$displays->register(new Display(Standard\BoolInfo::KIND, fn($value) => is_bool($value) ? ($value ? "true" : "false") : Display::INVALID));

		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["if"], fn(bool $value, string $then, string $else) : string => $value ? $then : $else);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["and"], fn(bool $v1, bool $v2) : bool => $v1 && $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["or"], fn(bool $v1, bool $v2) : bool => $v1 || $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["xor"], fn(bool $v1, bool $v2) : bool => $v1 !== $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["not"], fn(bool $value) : bool => !$value);
	}
}
