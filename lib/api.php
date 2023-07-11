<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use Closure;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\KindHelp;
use Shared\SOFe\InfoAPI\Mapping;

use function is_array;
use function strtolower;

final class InfoAPI {
	public const INVALID = Display::INVALID;

	/**
	 * Describes how to display a new kind.
	 * Also registers the mapping of this kind to the object type
	 * for use in addMapping reflections.
	 *
	 * The first parameter in the Closure is the type to be used for reflections in addMapping in the future.
	 * It must be a type defined by the plugin.
	 *
	 * @template T
	 * @param Closure(T $display, CommandSender $sender): string $display
	 */
	public static function addKind(
		string $kind,
		Closure $display,
		?string $shortName = null,
		?string $help = null,
	) : void {
		ReflectUtil::addClosureDisplay(self::defaultIndices(), $kind, $display);

		if ($help !== null) {
			self::defaultIndices()->registries->kindHelps->register(new KindHelp($kind, $shortName, $help));
		}
	}

	/**
	 * @param string|string[] $aliases
	 */
	public static function addMapping(
		Plugin $plugin,
		string|array $aliases,
		Closure $closure,
		?Closure $watchChanges = null,
		bool $isImplicit = false,
		string $help = "",
	) : void {
		ReflectUtil::addClosureMapping(
			indices: self::defaultIndices(),
			namespace: strtolower($plugin->getName()),
			names: is_array($aliases) ? $aliases : [$aliases],
			closure: $closure,
			watchChanges: $watchChanges,
			isImplicit: $isImplicit,
			help: $help,
		);
	}

	private static ?Indices $indices = null;

	public static function defaultIndices() : Indices {
		if (self::$indices === null) {
			self::$indices = Indices::withDefaults(Registries::singletons());
		}

		return self::$indices;
	}
}
