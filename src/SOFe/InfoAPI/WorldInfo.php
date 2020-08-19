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

class WorldInfo extends Info{
	/** @var World */
	private $world;

	public function __construct(World $level){
		$this->world = $level;
	}

	public function getWorld() : World{
		return $this->world;
	}

	public function toString() : string{
		return $this->world->getFolderName();
	}

	/**
	 * @param InfoRegistry $registry
	 *
	 * @internal Used by InfoAPI to register details
	 */
	public static function register(InfoRegistry $registry) : void{
		$registry->addDetails(["pocketmine.level.custom-name", "pocketmine.world.custom-name"], static function(WorldInfo $info){
			return new StringInfo($info->world->getDisplayName());
		});
		$registry->addDetails(["pocketmine.level.name", "pocketmine.world.name", "pocketmine.level.folder-name", "pocketmine.world.folder-name"], static function(WorldInfo $info){
			return new StringInfo($info->world->getFolderName());
		});
		$registry->addDetails(["pocketmine.level.time", "pocketmine.world.time"], static function(WorldInfo $info){
			return new NumberInfo($info->world->getTime()); // TODO better formatting: TimeInfo
		});
		$registry->addDetails(["pocketmine.level.seed", "pocketmine.world.time"], static function(WorldInfo $info){
			return new NumberInfo($info->world->getSeed());
		});
	}
}
