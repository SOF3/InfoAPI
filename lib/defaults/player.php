<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use Generator;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\event\player\PlayerToggleGlideEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\player\PlayerToggleSwimEvent;
use pocketmine\player\Player;
use pocketmine\world\Position;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\KindHelp;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\InitContext;
use SOFe\InfoAPI\ReflectUtil;
use function ceil;

final class Players {
	public static function register(InitContext $initCtx, Indices $indices) : void {
		$indices->registries->kindHelps->register(new KindHelp(Standard\PlayerInfo::KIND, "Player", "An online player"));
		$indices->registries->displays->register(new Display(
			Standard\PlayerInfo::KIND,
			fn($value) => $value instanceof Player ? $value->getName() : Display::INVALID,
		));

		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["name"], fn(Player $v) : string => $v->getName(),
			help: "Player username",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["nameTag"], fn(Player $v) : string => $v->getNameTag(),
			help: "Player name tag",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["displayName"], fn(Player $v) : string => $v->getDisplayName(),
			help: "Player display name",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["pos", "position", "loc", "location"], fn(Player $v) : Position => $v->getPosition(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerMoveEvent::class),
			isImplicit: true,
			help: "Player foot position",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["eyePos", "eyePosition"], fn(Player $v) : Position => Position::fromObject($v->getEyePos(), $v->getWorld()),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerMoveEvent::class),
			help: "Player eye position",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["standing"], fn(Player $v) : Position => new Position(
				$v->getPosition()->getFloorX(),
				(int) ceil($v->getPosition()->y),
				$v->getPosition()->getFloorZ(),
				$v->getWorld(),
			),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerMoveEvent::class),
			help: "The position of the block player is standing on",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["sneaking"], fn(Player $v) : bool => $v->isSneaking(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerToggleSneakEvent::class),
			help: "Whether the player is sneaking",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["flying"], fn(Player $v) : bool => $v->isFlying(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerToggleFlightEvent::class),
			help: "Whether the player is flying",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["swimming"], fn(Player $v) : bool => $v->isSwimming(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerToggleSwimEvent::class),
			help: "Whether the player is swimming",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["sprinting"], fn(Player $v) : bool => $v->isSprinting(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerToggleSprintEvent::class),
			help: "Whether the player is sprinting",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["gliding"], fn(Player $v) : bool => $v->isGliding(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerToggleGlideEvent::class),
			help: "Whether the player is gliding",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["alive"], fn(Player $v) : bool => $v->isAlive(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerDeathEvent::class, PlayerRespawnEvent::class),
			help: "Whether the player is alive",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["dead"], fn(Player $v) : bool => !$v->isAlive(),
			watchChanges: fn(Player $player) => self::watchPlayer($initCtx, $player, PlayerDeathEvent::class, PlayerRespawnEvent::class),
			help: "Whether the player is dead",
		);
		ReflectUtil::addClosureMapping(
			$indices, "infoapi", ["allowFlight", "canFly"], fn(Player $v) : bool => $v->getAllowFlight(),
			help: "Whether the player can fly",
		);
	}

	/**
	 * @param class-string<PlayerEvent|PlayerDeathEvent> $events
	 */
	private static function watchPlayer(InitContext $initCtx, Player $player, string ...$events) : Generator {
		return $initCtx->watchEvent(
			events: $events,
			key: $player->getName(),
			interpreter: fn(PlayerEvent|PlayerDeathEvent $event) => $event->getPlayer()->getName(),
		)->asGenerator();
	}
}
