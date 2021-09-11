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

use function assert;
use function get_class;
use Closure;
use SOFe\InfoAPI\Ast\ChildName;
use SOFe\InfoAPI\Graph\{Edge, Graph};

final class InfoAPI {
	private Graph $graph;
	/** @phpstan-var array<string, Template> */
	private array $cache;

	private function __construct() {
		$this->graph = new Graph;
		$this->cache = [];
	}

	/**
	 * @internal Only for internal testing use.
	 */
	public function getGraph() : Graph {
		return $this->graph;
	}

	/**
	 * Register a child info for infos of type `$class`.
	 *
	 * The name should be a unique dot-delimited string with increasing specificness.
	 * It is recommended to use the format `plugin.info`
	 * (or `plugin.module.info` if the plugin has multiple modules),
	 * e.g. the money of a player from the `MultiEconomy` plugin
	 * can be written as `multieconomy.money`.
	 *
	 * It is advised that info names be as simple as possible and not to contain more than one word
	 * (except the plugin name part, which should just be the lowercase of their exact name).
	 * In the case that it is inevitable (plugin names),
	 * names should be registered in the `camelCase` format.
	 * However, infos are matched case-insensitively,
	 * so `fooBar` and `foobar` are still the same name.
	 *
	 * @template P of Info
	 * @template C of Info
	 * @phpstan-param class-string<P> $parent  The parent info class that this child can be resolved from.
	 * @phpstan-param class-string<C> $child   The child info class resolved into.
	 * @phpstan-param string          $fqn     The fully-qualified, dot-separated name of this child info.
	 * @phpstan-param Closure(P): ?C  $resolve A closure to resolve the parent info into a child info,
	 *                                            or `null` if not available for that instance.
	 */
	static public function provideInfo(string $parent, string $child, string $fqn, Closure $resolve, ?self $self = null) : Edge {
		$self = $self ?? self::getInstance();
		$wrapper = static function(Info $info) use($parent, $resolve) : ?Info {
			assert($info instanceof $parent, "InfoAPI internal error: pass info of type " . get_class($info) . " to resolver of type $parent");
			return $resolve($info);
		};
		$edge = Edge::parentChild(ChildName::parse($fqn), $wrapper);
		$self->graph->insert($parent, $child, $edge);
		$self->cache = [];
		return $edge;
	}

	/**
	 * Registers a fallback info for infos of type `$class`.
	 *
	 * If a template writes `{foo bar}`, but `bar` is not found in `foo` (`FooInfo`),
	 * InfoAPI will resort to searching `bar` in each fallback info of `FooInfo`.
	 * Furthermore, if a fallback info has its own fallback info,
	 * the fallback-fallback info will be transitively searched on.
	 * The search follows a depth-first order,
	 * and fallbacks of the same type are resolved in the order they are registered.
	 *
	 * `ChildInfo` should only be the fallback of `ParentInfo` if this is intended by
	 * the plugin that declares `ParentInfo`
	 * (or the plugin that declares `ChildInfo`, although this is discouraged).
	 * Plugins adding fallback info should be aware of possible infinite recursion
	 * if a loop in fallbacks is detected.
	 *
	 * @template P of Info
	 * @template C of Info
	 * @phpstan-param class-string<P> $base     The base info class that this fallback is provided for.
	 * @phpstan-param class-string<C> $fallback The fallback info class.
	 * @phpstan-param Closure(P): ?C  $resolve  A closure to resolve the parent info into a child info,
	 *                                          or `null` if not available for that instance.
	 */
	static public function provideFallback(string $base, string $fallback, Closure $resolve, ?self $self = null) : Edge {
		$self = $self ?? self::getInstance();
		$wrapper = static function(Info $info) use($base, $resolve) : ?Info {
			assert($info instanceof $base, "InfoAPI internal error: pass info of type " . get_class($info) . " to resolver of type $base");
			return $resolve($info);
		};
		$edge = Edge::fallback($wrapper);
		$self->graph->insert($base, $fallback, $edge);
		$self->cache = [];
		return $edge;
	}

	static public function resolve(string $templateString, Info $context, bool $cache = true, ?self $self = null) : string {
		$template = self::compile(get_class($context), $templateString, $cache, $self ?? self::getInstance());
		return $template->resolve($context);
	}

	/**
	 * @phpstan-param class-string<Info> $source
	 */
	static private function compile(string $source, string $templateString, bool $cache, self $self) : Template {
		if(isset($self->cache["$source:$templateString"])) {
			return $self->cache["$source:$templateString"];
		}

		$template = Template::create($templateString, $source, $self->graph);
		if($cache) {
			$self->cache["$source:$templateString"] = $template;
		}

		return $template;
	}

	// SINGLETON BOILERPLATE //
	/* {{{ */
	private static ?InfoAPI $instance = null;

	static private function getInstance() : InfoAPI {
		return self::$instance = self::$instance ?? new self;
	}

	/**
	 * @internal For unit tests only, do not use.
	 */
	static public function createForTesting() : self {
		return new self;
	}
	/* }}} */
}
