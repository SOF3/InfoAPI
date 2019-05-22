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

use pocketmine\block\Block;

/**
 * Represents a block type.
 * If `$hasPosition` is set to true, represents a block of known type at a specific position.
 */
class BlockInfo extends Info{
	/** @var Block */
	private $block;
	/** @var bool */
	private $hasPosition;

	public function __construct(Block $block, bool $hasPosition = true){
		$this->block = $block;
		$this->hasPosition = $hasPosition;
	}

	public function getBlock() : Block{
		return $this->block;
	}

	public function hasPosition() : bool{
		return $this->hasPosition;
	}

	public function toString() : string{
		return $this->block->getName();
	}

	/**
	 * @param InfoRegistry $registry
	 *
	 * @internal Used by InfoAPI to register details
	 */
	public static function register(InfoRegistry $registry) : void{
		$registry->addDetail(self::class, "pocketmine.block.id", static function(BlockInfo $info){
			return new NumberInfo($info->block->getId());
		});
		$registry->addDetail(self::class, "pocketmine.block.damage", static function(BlockInfo $info){
			return new NumberInfo($info->block->getDamage());
			});
		$registry->addDetail(self::class, "pocketmine.block.name", static function(BlockInfo $info){
			return new NumberInfo($info->block->getId());
		});

		$registry->addFallback(self::class, static function(BlockInfo $info){
			return $info->hasPosition ? new PositionInfo($info->block) : null;
		});
	}
}
