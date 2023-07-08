<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use Closure;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\ReflectHint;
use Shared\SOFe\InfoAPI\Registry;
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
	) : void {
		/** @var Registry<Display> $displays */
		$displays = RegistryImpl::getInstance(Display::$global);

		/** @var Registry<ReflectHint> $hints */
		$hints = RegistryImpl::getInstance(ReflectHint::$global);

		ReflectUtil::addClosureDisplay($displays, $hints, $kind, $display);
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
	) : void {
		/** @var Registry<Mapping> $mappings */
		$mappings = RegistryImpl::getInstance(Mapping::$global);

		$hints = ReflectHintIndex::getInstance();

		ReflectUtil::addClosureMapping(
			mappings:$mappings,
			hints: $hints,
			namespace: strtolower($plugin->getName()),
			names: is_array($aliases) ? $aliases : [$aliases],
			closure: $closure,
			watchChanges: $watchChanges,
			isImplicit: $isImplicit,
		);
	}
}
