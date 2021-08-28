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

namespace SOFe\InfoAPI;

use function count;
use function explode;
use function get_class;
use function sprintf;

/**
 * Represents something that can be a step or a result in info resolution.
 *
 * All Info subclasses MUST extend Info directly. No immediate subclasses are allowed.
 */
abstract class Info{
	/**
	 * The string to get converted to if this info is used as a result
	 *
	 * @return string
	 */
	abstract public function toString() : string;

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
