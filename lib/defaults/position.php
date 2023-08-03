<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\KindHelp;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\ReflectUtil;
use function sprintf;

final class Positions {
	public static function register(Indices $indices) : void {
		$indices->registries->kindHelps->register(new KindHelp(Standard\PositionInfo::KIND, "Position", "A physical position in the game world"));
		$indices->registries->displays->register(new Display(
			Standard\PositionInfo::KIND,
			fn($value) => $value instanceof Position ? sprintf("(%s, %s, %s) @ %s", $value->x, $value->y, $value->z, $value->world?->getDisplayName() ?? "null") : Display::INVALID,
		));

		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["x"], fn(Position $v) : float => $v->x,
			help: "X-coordinate of the position",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["y"], fn(Position $v) : float => $v->y,
			help: "Y-coordinate of the position",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["z"], fn(Position $v) : float => $v->z,
			help: "Z-coordinate of the position",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["world"], fn(Position $v) : ?World => $v->world,
			help: "World of the position",
		);

		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["add", "plus"],
			fn(Position $v, Vector3 $vector) : Position => Position::fromObject($v->addVector($vector), $v->world),
			help: "Move along the vector",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["diff", "difference", "sub", "minus"],
			fn(Position $v, Position $from) : ?Vector3 => $v->world === $from->world ? $v->subtractVector($from) : null,
			help: "The vector from the `from` position to this position",
		);

		ReflectUtil::addClosureMapping($indices, "infoapi", ["dist", "distance"], fn(Position $v, Position $other) : float => $other->distance($v), help: "Distance between two positions");
	}
}

final class Vectors {
	public static function register(Indices $indices) : void {
		$indices->registries->kindHelps->register(new KindHelp(Standard\VectorInfo::KIND, "Vector", "A relative vector representing a direction and magnitude in 3D"));
		$indices->registries->displays->register(new Display(
			Standard\VectorInfo::KIND,
			fn($value) => $value instanceof Vector3 ? sprintf("(%s, %s, %s)", $value->x, $value->y, $value->z) : Display::INVALID,
		));

		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["x"], fn(Position $v) : float => $v->x,
			help: "X-component of this vector",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["y"], fn(Position $v) : float => $v->y,
			help: "Y-component of this vector",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["z"], fn(Position $v) : float => $v->z,
			help: "Z-component of this vector",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["add", "plus"],
			fn(Vector3 $v, Vector3 $other) : Vector3 => $v->addVector($other),
			help: "Sum of two vectors",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["sub", "subtract", "minus"],
			fn(Vector3 $v, Vector3 $other) : Vector3 => $v->subtractVector($other),
			help: "Subtract two vectors",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["mul", "mult", "multiply", "times", "scale"],
			fn(Vector3 $v, float $scale) : Vector3 => $v->multiply($scale),
			help: "Multiply a vector",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["div", "divide"],
			fn(Vector3 $v, float $scale) : Vector3 => $v->divide($scale),
			help: "Divide a vector",
		);

		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["len", "length", "mod", "modulus", "mag", "magnitude", "norm"],
			fn(Vector3 $v) : float => $v->length(),
			help: "Length of a vector",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["unit", "dir", "direction"], fn(Vector3 $v) : Vector3 => $v->normalize(),
			help: "A unit vector in the same direction with length 1",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["withLength"], fn(Vector3 $v, float $length) : Vector3 => $v->multiply($length / $v->length()),
			help: "A vector in the same direction with the specified length",
		);

		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["dot"],
			fn(Vector3 $v, Vector3 $other) : float => $v->dot($other),
			help: "Compute the dot product of two vectors",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["cross"],
			fn(Vector3 $v, Vector3 $other) : Vector3 => $v->cross($other),
			help: "Compute the cross product of two vectors",
		);
	}
}