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

use function array_keys;
use function count;
use function get_class;
use function is_string;
use function strlen;
use function strpos;
use function substr;
use function trim;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use pocketmine\Server;
use pocketmine\utils\Utils;

/**
 * @deprecated Use AnonInfo or SimpleInfo instead.
 * @link AnonInfo
 * @link SimpleInfo
 */
abstract class ContextInfo extends Info {
	/** @phpstan-var array<string, true> */
	private static array $registered = [];

	public function toString() : string {
		throw new RuntimeException("SimpleInfo must not be returned as a provided info");
	}

	/**
	 * Creates a new instance from an associative array.
	 *
	 * @param string              $namespace the name of your plugin, optionally followed by `.module` if the plugin contains many modules
	 * @param array<string, Info> $data
	 */
	final public function __construct(string $namespace, array $data) {
		$class = new ReflectionClass(get_class($this));

		if(!isset(self::$registered[$class->name])) {
			self::register($namespace);
			self::$registered[$class->name] = true;
		}

		foreach($class->getProperties() as $property) {
			if($property->isStatic()) {
				continue;
			}

			if(!isset($data[$property->name])) {
				throw new RuntimeException("Missing key \"$property->name\" in arguments");
			}

			$property->setValue($this, $data[$property->name]);
			unset($data[$property->name]);
		}

		if(count($data) > 0) {
			$key = array_keys($data)[0];
			throw new RuntimeException("Unknown key \"$key\" in arguments");
		}
	}

	private function register(string $namespace, ?InfoAPI $api = null) : void {
		$class = new ReflectionClass(get_class($this));
		foreach($class->getProperties() as $property) {
			if($property->isStatic()) {
				continue;
			}

			if(!$property->isPublic()) {
				throw new RuntimeException("ContextInfo field ({$class->name}::{$property->name}) must be public");
			}

			$ty = $property->getType();
			if(!($ty instanceof ReflectionNamedType)) {
				throw new RuntimeException("ContextInfo field ({$class->name}::{$property->name}) must explicitly declare their type as a single subclass of " . Info::class);
			}

			$tyName = $ty->getName();
			try {
				/** @phpstan-var class-string<Info> $tyName */
				$propertyClass = new ReflectionClass($tyName);
			} catch(ReflectionException $e) {
				throw new RuntimeException("ContextInfo field ({$class->name}::{$property->name}) must explicitly declare their type as a single subclass of " . Info::class);
			}

			if(!$propertyClass->isSubclassOf(Info::class)) {
				throw new RuntimeException("ContextInfo field ({$class->name}::{$property->name}) must explicitly declare their type as a single subclass of " . Info::class);
			}

			$doc = $property->getDocComment();
			if(!is_string($doc)) {
				$doc = "";
			}

			if(strlen($doc) > 5) {
				$desc = substr($doc, 3, -2);
				$desc = trim($desc);
				$pos = strpos($desc, "\n");
				if($pos !== false) {
					$desc = substr($desc, 0, $pos);
				}
			} else {
				$desc = "";
			}

			$edge = InfoAPI::provideInfo($class->getName(), $tyName, "$namespace.$property->name", static function(ContextInfo $info) use($property) : Info {
				return $property->getValue($info);
			})
				->setMetadata("description", $doc);
			foreach(Utils::parseDocComment($doc) as $k => $v) {
				$edge->setMetadata($k, $v);
			}
		}

		InfoAPI::provideFallback($class->getName(), CommonInfo::class, fn($info) => new CommonInfo(Server::getInstance()));
	}
}
