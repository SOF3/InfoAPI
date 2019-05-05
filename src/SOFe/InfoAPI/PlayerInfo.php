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
use pocketmine\Player;

class PlayerInfo extends Info{
	/** @var Player */
	private $player;

	public function __construct(Player $player){
		$this->player = $player;
	}

	public function defaults(InfoResolveEvent $event) : void{
		$event->match("pocketmine.name", function() : Info{
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
			return new RatioInfo($this->player->getHealth(), $this->player->getMaxHealth());
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
