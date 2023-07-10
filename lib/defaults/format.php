<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use pocketmine\Server;
use pocketmine\utils\TextFormat;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\ReflectUtil;

final class Formats {
	public static function register(Indices $indices) : void {
		foreach ([
			[TextFormat::BLACK, "black"],
			[TextFormat::DARK_BLUE, "darkBlue"],
			[TextFormat::DARK_GREEN, "darkGreen"],
			[TextFormat::DARK_AQUA, "darkAqua"],
			[TextFormat::DARK_RED, "darkRed"],
			[TextFormat::DARK_PURPLE, "darkPurple"],
			[TextFormat::GOLD, "gold"],
			[TextFormat::GRAY, "gray"],
			[TextFormat::DARK_GRAY, "darkGray"],
			[TextFormat::BLUE, "blue"],
			[TextFormat::GREEN, "green"],
			[TextFormat::AQUA, "aqua"],
			[TextFormat::RED, "red"],
			[TextFormat::LIGHT_PURPLE, "lightPurple"],
			[TextFormat::YELLOW, "yellow"],
			[TextFormat::WHITE, "white"],
			[TextFormat::MINECOIN_GOLD, "minecoinGold"],
			[TextFormat::OBFUSCATED, "obfuscated"],
			[TextFormat::BOLD, "bold"],
			[TextFormat::STRIKETHROUGH, "strikethrough"],
			[TextFormat::UNDERLINE, "underline"],
			[TextFormat::ITALIC, "italic"],
		] as [$code, $name]) {
			ReflectUtil::addClosureMapping(
				$indices, "infoapi", [$name], fn(Server $_) : string => $code,
				help: "Format the subsequent text as $name.",
			);
		}
	}
}
