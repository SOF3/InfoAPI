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

/**
 * @internal This class is only for InfoAPI internal setup
 */
final class Defaults {
	public const CLASSES = [
		StringInfo::class,
		NumberInfo::class,
		RatioInfo::class,
		PositionInfo::class,
		WorldInfo::class,
		BlockTypeInfo::class,
		BlockInfo::class,
		CommonInfo::class,
		FormatInfo::class,
		PlayerInfo::class,
		TimeInfo::class,
		DurationInfo::class,
	];

	static public function initAll(?InfoAPI $api) : void {
		foreach(self::CLASSES as $class) {
			$class::init($api);
		}
	}
}
