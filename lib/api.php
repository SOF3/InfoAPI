<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use Closure;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use RuntimeException;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\KindHelp;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Registry;
use SOFe\AwaitGenerator\Traverser;
use SOFe\InfoAPI\Template\GetOrWatch;
use SOFe\InfoAPI\Template\RenderedGroup;

use function get_class;
use function is_array;
use function is_object;
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
	 * @param Closure(T $display, ?CommandSender $sender): string $display
	 */
	public static function addKind(
		Plugin $plugin,
		string $kind,
		Closure $display,
		?string $shortName = null,
		?string $help = null,
	) : void {
		ReflectUtil::addClosureDisplay(self::defaultIndices($plugin), $kind, $display);

		if ($shortName !== null || $help !== null) {
			self::defaultIndices($plugin)->registries->kindHelps->register(new KindHelp($kind, $shortName, $help));
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
			indices: self::defaultIndices($plugin),
			namespace: strtolower($plugin->getName()),
			names: is_array($aliases) ? $aliases : [$aliases],
			closure: $closure,
			watchChanges: $watchChanges,
			isImplicit: $isImplicit,
			help: $help,
		);
	}

	/** @var array<string, Ast\Template> */
	private static array $templates = [];

	public static function parseAst(string $template, bool $cache = true) : Ast\Template {
		if ($cache) {
			return Ast\Parse::parse($template);
		}

		return self::$templates[$template] ??= Ast\Parse::parse($template);
	}

	private const ANONYMOUS_KIND = "infoapi/anonymous";

	/**
	 * @param array<string, mixed> $context
	 */
	public static function render(Plugin $plugin, string $template, array $context, ?CommandSender $sender = null, bool $cacheTemplate = true) : string {
		$group = self::renderTemplate($plugin, new Template\Get, $template, $context, $sender, $cacheTemplate);
		return $group->get();
	}

	/**
	 * @param array<string, mixed> $context
	 * @return Traverser<string>
	 */
	public static function renderContinuous(Plugin $plugin, string $template, array $context, ?CommandSender $sender = null, bool $cacheTemplate = true) : Traverser {
		$group = self::renderTemplate($plugin, new Template\Watch, $template, $context, $sender, $cacheTemplate);
		return $group->watch();
	}

	/**
	 * @template R of Template\RenderedElement
	 * @template G of Template\RenderedGroup
	 * @template T of GetOrWatch<R, G>
	 * @param T $getOrWatch
	 * @param array<string, mixed> $context
	 * @return G
	 */
	private static function renderTemplate(Plugin $plugin, GetOrWatch $getOrWatch, string $template, array $context, ?CommandSender $sender, bool $cacheTemplate) : RenderedGroup {
		$ast = self::parseAst($template, cache: $cacheTemplate);

		/** @var Registry<Mapping> $localMappings */
		$localMappings = new RegistryImpl;
		$indices = self::defaultIndices($plugin)->readonly();
		$indices->namedMappings = $indices->namedMappings->cloned();
		$indices->namedMappings->addLocalRegistry(0, $localMappings);

		foreach ($context as $key => $value) {
			$standardType = is_object($value) ? get_class($value) : ReflectUtil::getStandardType($value);
			$targetKind = $indices->hints->lookup($standardType);
			if ($targetKind === null) {
				throw new RuntimeException("Cannot determine kind of $key value, with type $standardType");
			}

			$localMappings->register(new Mapping(
				qualifiedName: [$key],
				sourceKind: self::ANONYMOUS_KIND,
				targetKind: $targetKind,
				isImplicit: false,
				parameters: [],
				map: fn($array) => is_array($array) ? $array[$key] : null,
				subscribe: null,
				help: "",
			));
		}

		$template = Template\Template::fromAst($ast, $indices, self::ANONYMOUS_KIND);
		return $template->render($context, $sender, $getOrWatch);
	}

	private static ?Indices $indices = null;

	public static function defaultIndices(Plugin $plugin) : Indices {
		if (self::$indices === null) {
			self::$indices = Indices::withDefaults(new PluginInitContext($plugin), Registries::singletons());
		}

		return self::$indices;
	}
}
