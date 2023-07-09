<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use pocketmine\player\Player;
use pocketmine\Server;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\ReflectHint;
use Shared\SOFe\InfoAPI\Registry;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\ReflectHintIndex;
use SOFe\InfoAPI\ReflectUtil;
use SOFe\InfoAPI\RegistryImpl;

final class Index {
	public const STANDARD_KINDS = [
		"string" => Standard\StringInfo::KIND,
		"int" => Standard\IntInfo::KIND,
		"float" => Standard\FloatInfo::KIND,
		"bool" => Standard\BoolInfo::KIND,
		Server::class => Standard\BaseContext::KIND,
		Player::class => Standard\PlayerInfo::KIND,
	];

	/**
	 * @param Registry<Display> $displays
	 * @param Registry<Mapping> $mappings
	 */
	public static function register(Registry $displays, Registry $mappings, ReflectHintIndex $hints) : void {
		Strings::register($displays, $mappings, $hints);
		Ints::register($displays, $mappings, $hints);
		Floats::register($displays, $mappings, $hints);
		Bools::register($displays, $mappings, $hints);
	}

	/**
	 * @param Registry<ReflectHint> $defaults
	 */
	public static function registerStandardKinds(Registry $defaults) : void {
		foreach (self::STANDARD_KINDS as $class => $kind) {
			/** @var class-string $class */ // HACK: $class may be primitive type names instead, but no need to fix it
			ReflectUtil::knowKind(hints: $defaults, class: $class, kind: $kind);
		}
	}

	/** @var ?array{Registry<Display>, Registry<Mapping>} */
	public static ?array $reused = null;

	/**
	 * @return array{Registry<Display>, Registry<Mapping>}
	 */
	private static function lazyInit() : array {
		if (self::$reused === null) {
			/** @var Registry<Display> $displays */
			$displays = new RegistryImpl;
			/** @var Registry<Mapping> $mappings */
			$mappings = new RegistryImpl;
			self::register($displays, $mappings, ReflectHintIndex::getInstance());

			return self::$reused = [$displays, $mappings];
		}

		return self::$reused;
	}

	/**
	 * @return Registry<Display>
	 */
	public static function reusedDisplays() : Registry {
		[$displays, $_mappings] = self::lazyInit();
		return $displays;
	}

	/**
	 * @return Registry<Mapping>
	 */
	public static function reusedMappings() : Registry {
		[$_displays, $mappings] = self::lazyInit();
		return $mappings;
	}
}
