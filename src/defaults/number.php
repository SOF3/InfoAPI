<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Registry;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\ReflectHintIndex;
use SOFe\InfoAPI\ReflectUtil;
use function abs;
use function ceil;
use function floor;
use function fmod;
use function intdiv;
use function is_float;
use function is_int;
use function max;
use function min;
use function pow;
use function round;

final class Ints {
	/**
	 * @param Registry<Display> $displays
	 * @param Registry<Mapping> $mappings
	 */
	public static function register(Registry $displays, Registry $mappings, ReflectHintIndex $hints) : void {
		$displays->register(new Display(Standard\IntInfo::KIND, fn($value) => is_int($value) ? (string) $value : Display::INVALID));

		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["asFloat"], fn(int $value) : float => (float) $value, isImplicit: true);

		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["abs", "absolute"], fn(int $v) : int => abs($v));
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["neg", "negate"], fn(int $v) : int => -$v);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["add", "plus", "sum"], fn(int $v1, int $v2) : int => $v1 + $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["sub", "subtract", "minus"], fn(int $v1, int $v2) : int => $v1 - $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["mul", "mult", "multiply", "times", "prod", "product"], fn(int $v1, int $v2) : int => $v1 * $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["div", "divide"], fn(int $v1, int $v2) : float => $v1 / $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["quotient"], fn(int $v1, int $v2) : int => intdiv($v1, $v2));
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["remainder", "rem", "modulus", "mod"], fn(int $v1, int $v2) : int => $v1 % $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["max", "maximum"], fn(int $v1, int $v2) : int => max($v1, $v2));
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["min", "minimum"], fn(int $v1, int $v2) : int => min($v1, $v2));
	}
}

final class Floats {
	/**
	 * @param Registry<Display> $displays
	 * @param Registry<Mapping> $mappings
	 */
	public static function register(Registry $displays, Registry $mappings, ReflectHintIndex $hints) : void {
		$displays->register(new Display(Standard\FloatInfo::KIND, fn($value) => is_int($value) || is_float($value) ? (string) $value : Display::INVALID));

		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["floor"], fn(float $value) : int => (int) floor($value));
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["ceil", "ceiling"], fn(float $value) : int => (int) ceil($value));
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["round"], fn(float $value) : int => (int) round($value));

		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["abs", "absolute"], fn(float $v) : float => abs($v));
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["neg", "negate"], fn(float $v) : float => -$v);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["add", "plus", "sum"], fn(float $v1, float $v2) : float => $v1 + $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["sub", "subtract", "minus"], fn(float $v1, float $v2) : float => $v1 - $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["mul", "mult", "multiply", "times", "prod", "product"], fn(float $v1, float $v2) : float => $v1 * $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["div", "divide"], fn(float $v1, float $v2) : float => $v1 / $v2);
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["quotient"], fn(float $v1, float $v2) : int => (int) ($v1 / $v2));
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["remainder", "rem", "modulus", "mod"], fn(float $v1, float $v2) : float => fmod($v1, $v2));
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["max", "maximum"], fn(float $v1, float $v2) : float => max($v1, $v2));
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["min", "minimum"], fn(float $v1, float $v2) : float => min($v1, $v2));

		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["pow", "power"], fn(float $v1, float $v2) : float => pow($v1, $v2));
		ReflectUtil::addClosureMapping($mappings, $hints, "infoapi", ["rec", "reciprocal", "inv", "inverse"], fn(float $value) : float => 1 / $value);
	}
}
