<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use pocketmine\utils\TextFormat;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\Indices;
use function is_string;

final class Formats {
	// This is not under the shared Standard namespace because plugins are not expected to register additional mappings related to formats.
	// Format is an internal kind that only exists to allow users to add formats.
	public const KIND = "infoapi/private/format";

	public static function register(Indices $indices) : void {
		$indices->registries->displays->register(new Display(self::KIND, fn($value) : string => is_string($value) ? $value : Display::INVALID));

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
			$indices->registries->mappings->register(new Mapping(
				qualifiedName: ["infoapi", "formats", $name],
				sourceKind: Standard\BaseContext::KIND,
				targetKind: self::KIND,
				isImplicit: false,
				parameters: [],
				map: fn($value) => $code,
				subscribe: null,
				help: "Format the subsequent text as $name.",
				metadata: [],
			));
		}
	}
}
