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

use pocketmine\Server;
use RuntimeException;

use function get_class;

abstract class AnonInfo extends Info {
	/** @phpstan-var array<string, true> */
	private static array $registered = [];
	/** @phpstan-var array<class-string, Info> */
	private array $fallbacks = [];

	public function toString() : string {
		throw new RuntimeException("AnonInfo must not be returned as a provided info");
	}

	/**
	 * Creates a new instance from an associative array.
	 *
	 * @param string              $namespace the name of your plugin, optionally followed by `.module` if the plugin contains many modules
	 * @param array<string, Info> $data
	 * @param ?list<Info>         $fallbacks
	 */
	final public function __construct(string $namespace, private array $data,  ?array $fallbacks = null) {
		$fallbacks = $fallbacks ?? [new CommonInfo(Server::getInstance())];
		foreach($fallbacks as $fallback) {
			$this->fallbacks[get_class($fallback)] = $fallback;
		}

		$self = get_class($this);
		if(!isset(AnonInfo::$registered[$self])) {
			AnonInfo::$registered[$self] = true;

			foreach($$data as $key => $value) {
				/** @var class-string<Info> $to */
				$to = get_class($value);
				InfoAPI::provideInfo($self, $to, "$namespace.$key",
					static function($instance) use($key) {
						return $instance->data[$key];
					},
				);
			}

			foreach($this->fallbacks as $value) {
				$to = get_class($value);
				InfoAPI::provideFallback($self, $to,
					static function($instance) use($to) {
						return $instance->fallbacks[$to];
					});
			}
		}
	}
}
