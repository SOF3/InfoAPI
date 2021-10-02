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

use pocketmine\world\Position;

final class PositionInfo extends Info {
	private Position $value;

	public function __construct(Position $value) {
		$this->value = $value;
	}

	public function getValue() : Position {
		return $this->value;
	}

	public function toString() : string {
		return sprintf("(%g, %g, %g) @ %s", $this->value->x, $this->value->y, $this->value->z, $this->value->getWorld()->getFolderName());
	}

	static public function getInfoType() : string {
		return "position";
	}

	static public function init(?InfoAPI $api) : void {
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.position.x",
			fn($info) => new NumberInfo($info->getValue()->getX()),
			$api)
			->setMetadata("description", "The X-coordinate of this position")
			->setMetadata("example", "128 (in (128, 64, 256))");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.position.y",
			fn($info) => new NumberInfo($info->getValue()->getY()),
			$api)
			->setMetadata("description", "The Y-coordinate of this position")
			->setMetadata("example", "64 (in (128, 64, 256))");
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.position.z",
			fn($info) => new NumberInfo($info->getValue()->getZ()),
			$api)
			->setMetadata("description", "The Z-coordinate of this position")
			->setMetadata("example", "256 (in (128, 64, 256))");
		InfoAPI::provideInfo(self::class, WorldInfo::class, "infoapi.position.world",
			fn($info) => new WorldInfo($info->getValue()->getWorld()),
			$api)
			->setMetadata("description", "The world containing this position");
	}
}