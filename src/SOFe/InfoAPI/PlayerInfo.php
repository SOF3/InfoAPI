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

use Generator;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\VoxelRayTrace;
use pocketmine\Player;

class PlayerInfo extends Info{
	/** @var Player */
	private $player;

	public function __construct(Player $player){
		$this->player = $player;
	}

	public function defaults(InfoResolveEvent $event) : void{
		$event->match("pocketmine.player.name", function() : Info{
				return new StringInfo($this->player->getName());
		});
		$event->matchAny(["pocketmine.nick", "pocketmine.display name"], function() : Info{
			return new StringInfo($this->player->getDisplayName());
		});
		$event->matchAny([
				"pocketmine.nametag",
				"pocketmine.name tag"
			], function() : Info{
				return new StringInfo($this->player->getNameTag());
		});
		$event->match("pocketmine.player.ip", function() : Info{
				return new StringInfo($this->player->getAddress());
		});
		$event->match("pocketmine.player.ping", function() : Info{
			return new NumberInfo($this->player->getPing());
		});
		$event->match("pocketmine.player.health", function() : Info{
			return new RatioInfo($this->player->getHealth() / 2, $this->player->getMaxHealth() / 2);
		});
		$event->match("pocketmine.player.yaw", function() : Info{
			return new NumberInfo($this->player->getYaw());
		});
		$event->match("pocketmine.player.pitch", function() : Info{
			return new NumberInfo($this->player->getPitch());
		});
		$event->match("pocketmine.player.eye", function() : Info{
			return new PositionInfo(Position::fromObject($this->player->asPosition()->add(0, $this->player->getEyeHeight(), 0), $this->player->asPosition()->getLevel()));
		});
		$event->match("pocketmine.player.block below", function() : Info{
			$position = $this->player->asPosition();
			$below = $position->floor()->subtract(0, 1, 0);
			if($below->y > Level::Y_MAX){
				$below->y = Level::Y_MAX;
			}elseif($below->y < 0){
				$below->y = 0;
			}
			/** @noinspection NullPointerExceptionInspection */
			$block = $position->getLevel()->getBlockAt($below->x, $below->y, $below->z);
			return new BlockInfo($block);
		});
		$event->matchAny(["pocketmine.player.facing block", "pocketmine.player.block facing"], function() : Info{
			$src = $this->player->asPosition();
			/** @var Level $level */
			$level = $src->getLevel();
			$src = $src->add(0, $this->player->getEyeHeight(), 0);
			$trace = VoxelRayTrace::inDirection($src, $this->player->getDirectionVector(), 128);
			foreach($trace as $pos){
				$block = $level->getBlockAt($pos->x, $pos->y, $pos->z, true, false);
				if($block->isSolid()){
					return new BlockInfo($block);
				}
			}
			return new BlockInfo($level->getBlockAt($src->x, $src->y, $src->z, true, false));
		});
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function toString() : string{
		return $this->player->getName();
	}

	public function fallbackInfos() : Generator{
		yield new PositionInfo($this->player->asPosition());
	}
}
