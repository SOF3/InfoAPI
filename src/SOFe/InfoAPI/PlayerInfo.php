<?php

/*
 * InfoAPI
 *
 * Copyright (C) 2019-2021 SOFe
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

declare(strict_types=1);

namespace SOFe\InfoAPI;

use pocketmine\player\Player;

final class PlayerInfo extends Info {
	private Player $value;

	public function __construct(Player $value) {
		$this->value = $value;
	}

	public function getValue() : Player {
		return $this->value;
	}

	public function toString() : string {
		return $this->value->getName();
	}

	static public function getInfoType() : string {
		return "player";
	}

	static public function init(?InfoAPI $api) : void {
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.player.name",
			fn($info) => ($info->getValue()->getName()),
			$api)
			->setMetadata("description", "The player name")
			->setMetadata("example", "Steve");
		InfoAPI::provideInfo(self::class, StringInfo::class, "infoapi.player.nick",
			fn($info) => new StringInfo($info->getValue()->getDisplayName()),
			$api)
			->setMetadata("description", "The player display name in chat")
			->setMetadata("example", "Steve");
		InfoAPI::provideInfo(self::class, StringInfo::class, "infoapi.player.nametag",
			fn($info) => new StringInfo($info->getValue()->getNameTag()),
			$api)
			->setMetadata("description", "The player nametag")
			->setMetadata("example", "Steve");
		InfoAPI::provideInfo(self::class, StringInfo::class, "infoapi.player.ip",
			fn($info) => new StringInfo($info->getValue()->getNetworkSession()->getIp()),
			$api)
			->setMetadata("description", "The player IP address")
			->setMetadata("example", "12.34.56.78");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.player.port",
			fn($info) => new NumberInfo($info->getValue()->getNetworkSession()->getPort()),
			$api)
			->setMetadata("description", "The player client port")
			->setMetadata("example", "61234");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.player.ping",
			fn($info) => new NumberInfo($info->getValue()->getNetworkSession()->getPing()),
			$api)
			->setMetadata("description", "The player ping, in milliseconds")
			->setMetadata("example", "15");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.player.health",
			fn($info) => new NumberInfo($info->getValue()->getHealth() / 2.),
			$api)
			->setMetadata("description", "The player health (number of hearts)")
			->setMetadata("example", "10");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.player.yaw",
			fn($info) => new NumberInfo($info->getValue()->getLocation()->getYaw()),
			$api)
			->setMetadata("description", "The player yaw orientation, in degrees")
			->setMetadata("example", "2700");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.player.pitch",
			fn($info) => new NumberInfo($info->getValue()->getLocation()->getPitch()),
			$api)
			->setMetadata("description", "The player pitch orientation, in degrees")
			->setMetadata("example", "90");
		InfoAPI::provideInfo(self::class, PositionInfo::class, "infoapi.player.eye",
			static function(PlayerInfo $info) : PositionInfo {
				$position = $info->getValue()->getPosition();
				return new PositionInfo(Position::fromObject(
					$position->add(0, $info->getValue()->getEyeHeight(), 0),
					$position->getWorld()
				));
			},
			$api)
			->setMetadata("description", "The player's eye position")
			->setMetadata("example", "(128, 65.8, 128)");
		InfoAPI::provideInfo(self::class, BlockInfo::class, "infoapi.player.blockBelow",
			static function(PlayerInfo $info) : BlockInfo {
				$position = $info->getValue()->asPosition();
				$below = $position->floor()->subtract(0, 1, 0);
				if($below->y > World::Y_MAX){
					$below->y = World::Y_MAX;
				}elseif($below->y < 0){
					$below->y = 0;
				}
				$block = $position->getWorld()->getBlockAt($below->x, $below->y, $below->z);
				return new BlockInfo($block);
			},
			$api)
			->setMetadata("description", "The block that the player steps on")
			->setMetadata("example", "grass at (128, 64, 128)");
		InfoAPI::provideInfo(self::class, BlockInfo::class, "infoapi.player.blockFacing",
			static function(PlayerInfo $info) : BlockInfo {
				$src = $info->getValue()->asPosition();
				/** @var World $world */
				$world = $src->getWorld();
				$src = $src->add(0, $info->getValue()->getEyeHeight(), 0);
				$trace = VoxelRayTrace::inDirection($src, $info->getValue()->getDirectionVector(), 128);
				foreach($trace as $pos){
					$block = $world->getBlockAt($pos->x, $pos->y, $pos->z, true, false);
					if($block->isSolid()){
						return new BlockInfo($block);
					}
				}
				return new BlockInfo($world->getBlockAt($src->x, $src->y, $src->z, true, false));
			},
			$api)
			->setMetadata("description", "The block that the player looks at")
			->setMetadata("example", "grass at (128, 64, 130)");

		InfoAPI::provideFallback(self::class, PositionInfo::class,
			fn($info) => $info->getValue()->asPosition(),
			$api)
			->setMetadata("description", "The position of the player feet");
	}
}
