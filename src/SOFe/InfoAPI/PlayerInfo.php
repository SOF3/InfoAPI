<?php

/*
 * InfoAPI
 *
 * Copyright (C) 2019 SOFe
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace SOFe\InfoAPI;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\math\VoxelRayTrace;
use pocketmine\player\Player;

class PlayerInfo extends Info{
	/** @var Player */
	private $player;

	public function __construct(Player $player){
		$this->player = $player;
	}

	/**
	 * @param InfoRegistry $registry
	 *
	 * @internal Used by InfoAPI to register details
	 */
	public static function register(InfoRegistry $registry) : void{
		$registry->addDetail("pocketmine.player.name", static function(PlayerInfo $info){
			return new StringInfo($info->player->getName());
		});
		$registry->addDetails(["pocketmine.nick", "pocketmine.display-name"], static function(PlayerInfo $info){
			return new StringInfo($info->player->getDisplayName());
		});
		$registry->addDetails([
				"pocketmine.nametag",
				"pocketmine.name-tag"
		], static function(PlayerInfo $info){
			return new StringInfo($info->player->getNameTag());
		});
		$registry->addDetail("pocketmine.player.ip", static function(PlayerInfo $info){
			return new StringInfo($info->player->getNetworkSession()->getIp());
		});
		$registry->addDetail("pocketmine.player.port", static function(PlayerInfo $info) {
			return new NumberInfo($info->player->getNetworkSession()->getPort());
		});
		$registry->addDetail("pocketmine.player.address", static function(PlayerInfo $info){
			// Maybe instead of ip/port/address etc separate, introduce NetworkInfo
			return new StringInfo($info->player->getNetworkSession()->getIp().":".$info->player->getNetworkSession()->getPort());
		});
		$registry->addDetail("pocketmine.player.ping", static function(PlayerInfo $info){
			return new NumberInfo($info->player->getNetworkSession()->getPing());
		});
		$registry->addDetail("pocketmine.player.health", static function(PlayerInfo $info){
			return new RatioInfo($info->player->getHealth() / 2, $info->player->getMaxHealth() / 2);
		});
		$registry->addDetail("pocketmine.player.yaw", static function(PlayerInfo $info){
			return new NumberInfo($info->player->getLocation()->getYaw());
		});
		$registry->addDetail("pocketmine.player.pitch", static function(PlayerInfo $info){
			return new NumberInfo($info->player->getPitch());
		});
		$registry->addDetail("pocketmine.player.eye", static function(PlayerInfo $info){
			$position = $info->player->getPosition();
			return new PositionInfo(Position::fromObject($position->add(0, $info->player->getEyeHeight(), 0), $position->getWorld()));
		});
		$registry->addDetail("pocketmine.player.block-below", static function(PlayerInfo $info){
			$position = $info->player->asPosition();
			$below = $position->floor()->subtract(0, 1, 0);
			if($below->y > World::Y_MAX){
				$below->y = World::Y_MAX;
			}elseif($below->y < 0){
				$below->y = 0;
			}
			/** @noinspection NullPointerExceptionInspection */
			$block = $position->getWorld()->getBlockAt($below->x, $below->y, $below->z);
			return new BlockInfo($block);
		});
		$registry->addDetails([
			"pocketmine.player.facing-block",
			"pocketmine.player.block-facing"
		], static function(PlayerInfo $info){
			$src = $info->player->asPosition();
			/** @var World $world */
			$world = $src->getWorld();
			$src = $src->add(0, $info->player->getEyeHeight(), 0);
			$trace = VoxelRayTrace::inDirection($src, $info->player->getDirectionVector(), 128);
			foreach($trace as $pos){
				$block = $world->getBlockAt($pos->x, $pos->y, $pos->z, true, false);
				if($block->isSolid()){
					return new BlockInfo($block);
				}
			}
			return new BlockInfo($world->getBlockAt($src->x, $src->y, $src->z, true, false));
		});

		$registry->addFallback(static function(PlayerInfo $info){
			return new PositionInfo($info->player->asPosition());
		});
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function toString() : string{
		return $this->player->getName();
	}
}
