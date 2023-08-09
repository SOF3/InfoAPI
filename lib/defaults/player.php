<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use Generator;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\event\player\PlayerToggleGlideEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\player\PlayerToggleSwimEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\KindMeta;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\InitContext;
use SOFe\InfoAPI\ReflectUtil;
use function ceil;
use function count;

final class Players {
	public static function register(InitContext $initCtx, Indices $indices) : void {
		$indices->registries->kindMetas->register(new KindMeta(Standard\PlayerInfo::KIND, "Player", "An online player", []));
		$indices->registries->displays->register(new Display(
			Standard\PlayerInfo::KIND,
			fn($value) => $value instanceof Player ? $value->getName() : Display::INVALID,
		));

		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["name"], fn(Player $v) : string => $v->getName(),
			help: "Player username",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["nameTag"], fn(Player $v) : string => $v->getNameTag(),
			help: "Player name tag",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["displayName"], fn(Player $v) : string => $v->getDisplayName(),
			help: "Player display name",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["pos", "position", "loc", "location"], fn(Player $v) : Position => $v->getPosition(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerMoveEvent::class),
			isImplicit: true,
			help: "Player foot position",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["eyePos", "eyePosition"], fn(Player $v) : Position => Position::fromObject($v->getEyePos(), $v->getWorld()),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerMoveEvent::class),
			help: "Player eye position",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["standing"], fn(Player $v) : Position => new Position(
				$v->getPosition()->getFloorX(),
				(int) ceil($v->getPosition()->y),
				$v->getPosition()->getFloorZ(),
				$v->getWorld(),
			),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerMoveEvent::class),
			help: "The position of the block player is standing on",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["sneaking"], fn(Player $v) : bool => $v->isSneaking(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerToggleSneakEvent::class),
			help: "Whether the player is sneaking",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["flying"], fn(Player $v) : bool => $v->isFlying(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerToggleFlightEvent::class),
			help: "Whether the player is flying",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["swimming"], fn(Player $v) : bool => $v->isSwimming(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerToggleSwimEvent::class),
			help: "Whether the player is swimming",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["sprinting"], fn(Player $v) : bool => $v->isSprinting(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerToggleSprintEvent::class),
			help: "Whether the player is sprinting",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["gliding"], fn(Player $v) : bool => $v->isGliding(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerToggleGlideEvent::class),
			help: "Whether the player is gliding",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["alive"], fn(Player $v) : bool => $v->isAlive(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerDeathEvent::class, PlayerRespawnEvent::class),
			help: "Whether the player is alive",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["dead"], fn(Player $v) : bool => !$v->isAlive(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerDeathEvent::class, PlayerRespawnEvent::class),
			help: "Whether the player is dead",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["allowFlight", "canFly"], fn(Player $v) : bool => $v->getAllowFlight(),
			help: "Whether the player can fly",
		);

		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["playerCount"], fn(Server $server) : int => count($server->getOnlinePlayers()),
			// TODO watchChanges
			help: "Number of online players",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi:player", ["player"], fn(string $name) : ?Player => Server::getInstance()->getPlayerExact($name),
			watchChanges: fn(string $name) => self::watchPlayerName($initCtx, $name, PlayerLoginEvent::class, PlayerQuitEvent::class),
			help: "Search information about an online player by name",
		);
	}

	/**
	 * @param class-string<PlayerEvent|PlayerDeathEvent> $events
	 */
	private static function watchPlayer(InitContext $initCtx, Player $player, string ...$events) : Generator {
		return self::watchPlayerName($initCtx, $player->getName(), ...$events);
	}

	/**
	 * @param class-string<PlayerEvent|PlayerDeathEvent> $events
	 */
	private static function watchPlayerName(InitContext $initCtx, string $playerName, string ...$events) : Generator {
		return $initCtx->watchEvent(
			events: $events,
			key: $playerName,
			interpreter: fn(PlayerEvent|PlayerDeathEvent $event) => $event->getPlayer()->getName(),
		)->asGenerator();
	}
}
