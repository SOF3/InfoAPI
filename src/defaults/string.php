<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Registry;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\ReflectHintIndex;
use SOFe\InfoAPI\ReflectUtil;

use function is_string;
use function mb_strtolower;
use function mb_strtoupper;

/**
 * Implements a standard string node.
 */
final class Strings {
	/**
	 * @param Registry<Display> $displays
	 * @param Registry<Mapping> $mappings
	 */
	public static function register(Registry $displays, Registry $mappings, ReflectHintIndex $hintsIndex) : void {
		$displays->register(new Display(Standard\StringInfo::KIND, fn($value) => is_string($value) ? $value : Display::INVALID));

		ReflectUtil::addClosureMapping($mappings, $hintsIndex, "infoapi", ["upper", "uppercase"], fn(string $string) : string => mb_strtoupper($string));
		ReflectUtil::addClosureMapping($mappings, $hintsIndex, "infoapi", ["lower", "lowercase"], fn(string $string) : string => mb_strtolower($string));
	}
}
