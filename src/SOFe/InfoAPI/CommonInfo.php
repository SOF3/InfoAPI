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

use function count;
use RuntimeException;
use pocketmine\Server;

final class CommonInfo extends Info {
	private Server $value;

	public function __construct(Server $value) {
		$this->value = $value;
	}

	public function getValue() : Server {
		return $this->value;
	}

	public function toString() : string {
		throw new RuntimeException("CommonInfo must not be returned as a provided info");
	}

	static public function getInfoType() : string {
		return "common";
	}

	static public function init(?InfoAPI $api) : void {
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.server.players",
			fn($info) => new RatioInfo(
				count($info->getValue()->getOnlinePlayers()),
				$info->getValue()->getMaxPlayers()
			),
			$api)
			->setMetadata("description", "Number of online players")
			->setMetadata("example", "16 / 20");
	}
}
