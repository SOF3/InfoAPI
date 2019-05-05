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

use pocketmine\level\Level;

class LevelInfo extends Info{
	/** @var Level */
	private $level;

	public function __construct(Level $level){
		$this->level = $level;
	}

	public function getLevel() : Level{
		return $this->level;
	}

	public function toString() : string{
		return $this->level->getFolderName();
	}

	public function defaults(InfoResolveEvent $event) : bool{
		return $event->match("pocketmine.custom name", function() : Info{
				return new StringInfo($this->level->getName());
			}) or $event->matchAny(["pocketmine.name", "pocketmine.folder name"], function() : Info{
				return new StringInfo($this->level->getFolderName());
			}) or $event->match("pocketmine.level.time", function() : Info{
				return new NumberInfo($this->level->getTime()); // TODO better formatting: TimeInfo
			}) or $event->match("pocketmine.level.seed", function() : Info{
				return new NumberInfo($this->level->getSeed());
			});
	}
}
