<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use Generator;
use pocketmine\block\Block;
use pocketmine\event\world\WorldEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\Server;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use pocketmine\world\World;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\KindMeta;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\InitContext;
use SOFe\InfoAPI\ReflectUtil;
use function count;

final class Worlds {
	public static function register(InitContext $initCtx, Indices $indices) : void {
		$indices->registries->kindMetas->register(new KindMeta(Standard\WorldInfo::KIND, "World", "A loaded world", []));
		$indices->registries->displays->register(new Display(
			Standard\WorldInfo::KIND,
			fn($value) => $value instanceof World ? $value->getFolderName() : Display::INVALID,
		));

		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["folderName", "name"], fn(World $v) : string => $v->getFolderName(),
			help: "World folder name",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["displayName"], fn(World $v) : string => $v->getDisplayName(),
			help: "World display name (the one in level.dat)",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["time"], fn(World $v) : int => $v->getTime(),
			help: "Accumulative in-game time of the world in ticks",
			watchChanges: fn(World $v) => self::metronome($initCtx),
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["timeOfDay"], fn(World $v) : int => $v->getTimeOfDay(),
			help: "In-game time-of-day of the world in ticks",
			watchChanges: fn(World $v) => self::metronome($initCtx),
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["seed"], fn(World $v) : int => $v->getSeed(),
			help: "Seed of the world",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["seed"], fn(World $v) : int => $v->getSeed(),
			help: "World seed",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["spawn"], fn(World $v) : Position => $v->getSpawnLocation(),
			help: "Spawn point set for the world",
		);

		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["worldCount"], fn(Server $server) : int => count($server->getWorldManager()->getWorlds()),
			help: "Number of loaded worlds",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["world"], fn(string $name) : ?World => Server::getInstance()->getWorldManager()->getWorldByName($name),
			help: "Search information about a loaded world by name",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["defaultWorld"], fn(Server $server) : ?World => $server->getWorldManager()->getDefaultWorld(),
			help: "The default world",
			watchChanges: fn(string $worldName) => self::watchWorldName($initCtx, $worldName, WorldLoadEvent::class, WorldUnloadEvent::class),
		);

		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["block"], fn(Position $pos) : ?Block => (
				$pos->getWorld()->isChunkLoaded($pos->getFloorX() >> Chunk::COORD_BIT_SIZE, $pos->getFloorZ() >> Chunk::COORD_BIT_SIZE) ?
				$pos->getWorld()->getBlock($pos->asVector3()) : null),
			help: "Get the actual block type at the position",
			watchChanges: fn(Position $pos) => $initCtx->watchBlock($pos)->asGenerator(),
		);
	}

	/**
	 * @param class-string<WorldEvent> $events
	 */
	private static function watchWorldName(InitContext $initCtx, string $worldName, string ...$events) : Generator {
		return $initCtx->watchEvent(
			events: $events,
			key: $worldName,
			interpreter: fn(WorldEvent $event) => $event->getWorld()->getFolderName(),
		)->asGenerator();
	}

	private static function metronome(InitContext $initCtx, int $period = 1) : Generator {
		return (function() use ($initCtx, $period) {
			while (true) {
				yield from $initCtx->sleep($period);
			}
		})();
	}
}

final class Blocks {
	public static function register(Indices $indices) : void {
		$indices->registries->kindMetas->register(new KindMeta(Standard\BlockTypeInfo::KIND, "Block type", "A type of block", []));
		$indices->registries->displays->register(new Display(
			Standard\BlockTypeInfo::KIND,
			fn($value) => $value instanceof Block ? $value->getName() : Display::INVALID,
		));

		ReflectUtil::addClosureMapping(
			$indices, "infoapi:world", ["name"], fn(Block $v) : string => $v->getName(),
			help: "Block name",
		);
	}
}
