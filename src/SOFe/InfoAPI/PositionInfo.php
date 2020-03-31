<?php

/*
 * InfoAPI
 *
 * Copyright (C) 2019-2020 SOFe
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

	public function getPosition() : Position{
		return $this->position;
	}

	public function toString() : string{
		/** @noinspection NullPointerExceptionInspection */
		return sprintf("(%g, %g, %g) @ %s", $this->position->x, $this->position->y, $this->position->z, $this->position->getLevel()->getFolderName());
	}

	/**
	 * @param InfoRegistry $registry
	 *
	 * @internal Used by InfoAPI to register details
	 */
	public static function register(InfoRegistry $registry) : void{
		$registry->addDetail(self::class, "pocketmine.pos.x", static function(PositionInfo $info){
			return new NumberInfo($info->position->getX());
		});
		$registry->addDetail(self::class, "pocketmine.pos.y", static function(PositionInfo $info){
			return new NumberInfo($info->position->getY());
		});
		$registry->addDetail(self::class, "pocketmine.pos.z", static function(PositionInfo $info){
			return new NumberInfo($info->position->getZ());
		});
		$registry->addDetails(self::class, [
			"pocketmine.pos.level",
			"pocketmine.pos.world",
		], static function(PositionInfo $info){
			return new LevelInfo($info->position->getLevel());
		});
	}
}
