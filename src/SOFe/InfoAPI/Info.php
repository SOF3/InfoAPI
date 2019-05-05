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

use Generator;
use function count;
use function explode;

/**
 * Represents something that can be a step or a result in info resolution
 */
abstract class Info{
	/**
	 * The string to get converted to if this info is used as a result
	 *
	 * @return string
	 */
	abstract public function toString() : string;

	/**
	 * If no info is resolved from the event listeners, this method is called to provide fallback values.
	 *
	 * It is guaranteed that `$event->getInfo() === $this`.
	 *
	 * @param InfoResolveEvent $event
	 */
	public function defaults(InfoResolveEvent $event) : void{
	}

	/**
	 * If no info is resolved from the event listeners, and `defaults()` does not resolve any values,
	 * this method would be called.
	 *
	 * It should yield instances of `Info`, on which new `InfoResolveEvent`s would be called.
	 * If an Info is successfully resolved from the fallback Info, the value would be used directly.
	 *
	 * This is comparable to a (multi-)inheritance mechanism.
	 * For example, `PlayerInfo` would yield `PositionInfo`, since `Player` contains all properties of `Position`.
	 *
	 * Each fallback info call involves a level of recursion. Beware infinite recursion loop.
	 *
	 * @return Generator
	 */
	public function fallbackInfos() : Generator{
		false && yield;
	}

	/**
	 * Returns a display name of this info type.
	 *
	 * Used in info browsers.
	 *
	 * @return string
	 */
	public function getInfoType() : string{
		$pieces = explode("\\", __CLASS__);
		return $pieces[count($pieces) - 1];
	}
}
