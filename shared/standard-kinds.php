<?php

declare(strict_types=1);

/**
 * Constants for standard kinds shared between instances of the shaded virion.
 *
 * Each kind has its own class to allow adding new standard kinds in the future.
 */
namespace Shared\SOFe\InfoAPI\Standard;

/**
 * The base context that is the implicit mapping target of resolve contexts.
 * Define mappings from this kind to expose "global functions".
 */
final class BaseContext {
	public const KIND = "infoapi/base";
}

/** An info of type `string`, representing a generic string. */
final class StringInfo {
	public const KIND = "infoapi/string";
}

/** An info of type `int`, representing a generic integer. */
final class IntInfo {
	public const KIND = "infoapi/integer";
}

/** An info of type `float`, representing a generic float. */
final class FloatInfo {
	public const KIND = "infoapi/float";
}

/** An info of type `bool`, representing a generic bool. */
final class BoolInfo {
	public const KIND = "infoapi/bool";
}

/** An info of type `Vector3`, representing a relative vector. */
final class VectorInfo {
	public const KIND = "infoapi/vector";
}

/** An info of type `\pocketmine\world\Position`, representing an in-game position. */
final class PositionInfo {
	public const KIND = "infoapi/position";
}

/** An info of type `\pocketmine\world\World`, representing a loaded world. */
final class WorldInfo {
	public const KIND = "infoapi/world";
}

/** An info of type `\pocketmine\player\Player`, representing an online player. */
final class PlayerInfo {
	public const KIND = "infoapi/player";
}

/**
 * An info of type `\pocketmine\block\Block`, representing a block type.
 *
 * In the BlockTypeInfo case, the position in the block is meaningless.
 */
final class BlockTypeInfo {
	public const KIND = "infoapi/blockType";
}
