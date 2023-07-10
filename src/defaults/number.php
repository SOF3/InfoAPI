<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\Indices;
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
	public static function register(Indices $indices) : void {
		$indices->registries->displays->register(new Display(Standard\IntInfo::KIND, fn($value) => is_int($value) ? (string) $value : Display::INVALID));

		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["asFloat"], fn(int $value) : float => (float) $value, isImplicit: true,
			help: "Convert the integre to a float",
		);

		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["abs", "absolute"], fn(int $v) : int => abs($v),
			help: "Take the absolute value.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["neg", "negate"], fn(int $v) : int => -$v,
			help: "Flip the positive/negative sign.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["add", "plus", "sum"], fn(int $v1, int $v2) : int => $v1 + $v2,
			help: "Add two numbers.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["sub", "subtract", "minus"], fn(int $v1, int $v2) : int => $v1 - $v2,
			help: "Subtract two numbers.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["mul", "mult", "multiply", "times", "prod", "product"], fn(int $v1, int $v2) : int => $v1 * $v2,
			help: "Multiply two numbers.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["div", "divide"], fn(int $v1, int $v2) : float => $v1 / $v2,
			help: "Divide two numbers.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["quotient"], fn(int $v1, int $v2) : int => intdiv($v1, $v2),
			help: "Divide two numbers and take the integer quotient.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["remainder", "rem", "modulus", "mod"], fn(int $v1, int $v2) : int => $v1 % $v2,
			help: "Divide two numbers and take the remainder.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["greater", "max", "maximum"], fn(int $v1, int $v2) : int => max($v1, $v2),
			help: "Take the greater of two numbers.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["less", "min", "minimum"], fn(int $v1, int $v2) : int => min($v1, $v2),
			help: "Take the less of two numbers.",
		);
	}
}

final class Floats {
	public static function register(Indices $indices) : void {
		$indices->registries->displays->register(new Display(Standard\FloatInfo::KIND, fn($value) => is_int($value) || is_float($value) ? (string) $value : Display::INVALID));

		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["floor"], fn(float $value) : int => (int) floor($value),
			help: "Round down the number.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["ceil", "ceiling"], fn(float $value) : int => (int) ceil($value),
			help: "Round up the number.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["round"], fn(float $value) : int => (int) round($value),
			help: "Round the number to the nearest integer.",
		);

		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["abs", "absolute"], fn(float $v) : float => abs($v),
			help: "Take the absolute value.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["neg", "negate"], fn(float $v) : float => -$v,
			help: "Flip the positive/negative sign.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["add", "plus", "sum"], fn(float $v1, float $v2) : float => $v1 + $v2,
			help: "Add two numbers.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["sub", "subtract", "minus"], fn(float $v1, float $v2) : float => $v1 - $v2,
			help: "Subtract two numbers.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["mul", "mult", "multiply", "times", "prod", "product"], fn(float $v1, float $v2) : float => $v1 * $v2,
			help: "Multiply two numbers.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["div", "divide"], fn(float $v1, float $v2) : float => $v1 / $v2,
			help: "Divide two numbers.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["quotient"], fn(float $v1, float $v2) : int => (int) ($v1 / $v2),
			help: "Divide two numbers and take the integer quotient.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["remainder", "rem", "modulus", "mod"], fn(float $v1, float $v2) : float => fmod($v1, $v2),
			help: "Divide two numbers and take the remainder.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["greater", "max", "maximum"], fn(float $v1, float $v2) : float => max($v1, $v2),
			help: "Take the greater of two numbers.",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["less", "min", "minimum"], fn(float $v1, float $v2) : float => min($v1, $v2),
			help: "Take the less of two numbers.",
		);

		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["pow", "power"], fn(float $v, float $exp) : float => pow($v, $exp),
			help: "Raise the number to the power \"exp\".",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["rec", "reciprocal", "inv", "inverse"], fn(float $value) : float => 1 / $value,
			help: "Take the reciprocal of a number, i.e. 1 divided by the number.",
		);
	}
}
