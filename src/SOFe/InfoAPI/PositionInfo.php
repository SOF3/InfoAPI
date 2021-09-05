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
			fn($info) => $info->getValue()->getX(),
			$api);
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.position.y",
			fn($info) => $info->getValue()->getY(),
			$api);
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.position.z",
			fn($info) => $info->getValue()->getZ(),
			$api);
		InfoAPI::provideInfo(self::class, WorldInfo::class, "infoapi.position.world",
			fn($info) => $info->getValue()->getZ(),
			$api);
	}
}
