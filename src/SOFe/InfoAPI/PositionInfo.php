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

use pocketmine\level\Position;
use function sprintf;

class PositionInfo extends Info{
	/** @var Position */
	private $position;

	public function __construct(Position $position){
		$this->position = $position;
	}

	public function toString() : string{
		/** @noinspection NullPointerExceptionInspection */
		return sprintf("%g %g %g %s", $this->position->x, $this->position->y, $this->position->z, $this->position->getLevel()->getFolderName());
	}

	public function defaults(InfoResolveEvent $event) : bool{
		$event->match("pocketmine.pos.x", function() : Info{
			return new NumberInfo($this->position->getX());
		}) or $event->match("pocketmine.pos.y", function() : Info{
			return new NumberInfo($this->position->getY());
		}) or $event->match("pocketmine.pos.z", function() : Info{
			return new NumberInfo($this->position->getZ());
		}) or $event->matchAny([
			"pocketmine.pos.level",
			"pocketmine.pos.world",
		], function(){
			return new LevelInfo($this->position->getLevel());
		});
	}
}
