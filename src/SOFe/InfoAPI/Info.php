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

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

use function array_slice;
use function explode;
use function get_class;

abstract class Info {
	/**
	 * Displays this info in a template.
	 *
	 * Usually, this should be equivalent ot the `name` field of a named info.
	 */
	abstract public function toString() : string;

	/**
	 * Displays the type of this info for a user setting up templates.
	 *
	 * This value is used in documentation and info browser.
	 */
	static public function getInfoType() : string {
		$comps = explode("\\", static::class);
		return array_slice($comps, -1)[0];
	}

	/**
	 * @param class-string<Info> $class
	 */
	static public function registerByReflection(string $namespace, string $class, ?int $filter = null, ?InfoAPI $api = null) : void {
		$reflect = new ReflectionClass($class);

		foreach($reflect->getProperties($filter) as $property) {
			if($property->isStatic() || !$property->isPublic()) {
				continue;
			}

			$type = $property->getType();
			if(!($type instanceof ReflectionNamedType)) {
				throw new ReflectionException("Property $property is untyped");
			}

			/** @var class-string<Info> $target */
			$target = $type->getName();
			$targetClass = new ReflectionClass($target);
			if(!($targetClass->isSubclassOf(Info::class))) {
				throw new ReflectionException("Property $property is untyped");
			}

			InfoAPI::provideInfo($class, $target, "$namespace." . $property->getName(), function(Info $info) use($property) : ?Info {
				return $property->getValue($info);
			}, $api);
		}
	}
}
