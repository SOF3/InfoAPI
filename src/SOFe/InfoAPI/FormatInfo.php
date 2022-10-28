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

use pocketmine\utils\TextFormat;
use RuntimeException;

final class FormatInfo extends Info {
	public function __construct() {}

	public function toString() : string {
		throw new RuntimeException("FormatInfo must not be returned as a provided info");
	}

	static public function getInfoType() : string {
		return "format";
	}

	static public function init(?InfoAPI $api) : void {
		InfoAPI::provideFallback(CommonInfo::class, self::class, fn($_) => new self, $api);

		foreach([
			"black" => TextFormat::BLACK,
			"darkBlue" => TextFormat::DARK_BLUE,
			"darkGreen" => TextFormat::DARK_GREEN,
			"darkAqua" => TextFormat::DARK_AQUA,
			"darkRed" => TextFormat::DARK_RED,
			"darkPurple" => TextFormat::DARK_PURPLE,
			"gold" => TextFormat::GOLD,
			"gray" => TextFormat::GRAY,
			"darkGray" => TextFormat::DARK_GRAY,
			"blue" => TextFormat::BLUE,
			"green" => TextFormat::GREEN,
			"aqua" => TextFormat::AQUA,
			"red" => TextFormat::RED,
			"lightPurple" => TextFormat::LIGHT_PURPLE,
			"yellow" => TextFormat::YELLOW,
			"white" => TextFormat::WHITE,
			"obfuscated" => TextFormat::OBFUSCATED,
			"bold" => TextFormat::BOLD,
			"strikethrough" => TextFormat::STRIKETHROUGH,
			"underline" => TextFormat::UNDERLINE,
			"italic" => TextFormat::ITALIC,
			"reset" => TextFormat::RESET,
			"line" => "\n",
		] as $name => $value) {
			InfoAPI::provideInfo(self::class, StringInfo::class, "infoapi.format.$name",
				fn($_) => new StringInfo($value), $api)
				->setMetadata("description", "Change subsequent text format to $name");
		}
	}
}
