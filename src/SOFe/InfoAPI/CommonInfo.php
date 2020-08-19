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

use pocketmine\Server;
use UnexpectedValueException;
use function count;

class CommonInfo extends Info{
	/** @var Server */
	private $server;

	public function __construct(Server $server){
		$this->server = $server;
	}

	/**
	 * @param InfoRegistry $registry
	 *
	 * @internal Used by InfoAPI to register details
	 */
	public static function register(InfoRegistry $registry) : void{
		$registry->addDetail("pocketmine.server.players", static function(CommonInfo $info){
			return new NumberInfo(count($info->server->getOnlinePlayers()));
		});
		$registry->addDetail("pocketmine.server.max-players", static function(CommonInfo $info){
			return new NumberInfo($info->server->getMaxPlayers());
		});
	}

	public function toString() : string{
		throw new UnexpectedValueException("CommonInfo must only be used as a fallback info");
	}
}
