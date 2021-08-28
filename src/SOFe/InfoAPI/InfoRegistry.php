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

use Closure;
use Generator;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
use function array_slice;
use function class_exists;
use function count;
use function explode;
use function get_class;
use function implode;
use function sprintf;
use function strpos;
use function substr_count;

final class InfoRegistry{
	/** @var InfoRegistry */
	private static $instance;

	/**
	 * Obtains the Server-global InfoRegistry instance.
	 *
	 * This method should only be used from other plugins.
	 */
	public static function getInstance() : InfoRegistry{
		if(self::$instance === null){
			self::$instance = $registry = new InfoRegistry();
			BlockInfo::register($registry);
			WorldInfo::register($registry);
			NumberInfo::register($registry);
			PlayerInfo::register($registry);
			PositionInfo::register($registry);
			RatioInfo::register($registry);
			StringInfo::register($registry);
		}
		return self::$instance;
	}

	/**
	 * An index to obtain a resolver by parent class and detail name.
	 * Each qualification level of each alias has its index in the second dimension.
	 *
	 * @var array<class-string<Info>, array<string, Closure(Info) : Info>>
	 */
	private $graph = [];

	/**
	 * Stores unique `addDetails` calls.
	 *
	 * @var array<class-string<Info>, PrincipalDetail[]>
	 */
	private $principalGraph = [];

	/**
	 * Stores the calls to `addFallback`.
	 *
	 * @var array<class-string<Info>, (Closure(Info): Info|null)[]>
	 */
	private $aliasMap = [];

	/**
	 * @internal This constructor is public only for unit testing.
	 */
	public function __construct(){
	}

	/**
	 * Adds an Info detail to a type.
	 * The closure will be passed instances of `$parentClass`,
	 * and should return a detail of the parent info as identified by `$name`.
	 *
	 * The name should be a dot-delimited string with increasing level of specificness,
	 * e.g. `pocketmine.level.name` can be used as `name`, `level.name` or `pocketmine.level.name`.
	 *
	 * @template T of Info
	 * @param class-string<T> $parentClass the parent type that the detail belongs to
	 * @param string $name the name to identify the detail with respect to the parent
	 * @param Closure(T): Info|null $childGetter
	 */
	public function addDetail(string $parentClass, string $name, Closure $childGetter) : void{
		$this->addDetails($parentClass, [$name], $childGetter);
	}


	/**
	 * Adds Info details to a type.
	 *
	 * This is equivalent to calling `addDetail` multiple times,
	 * with each `$name`
	 *
	 * @template T of Info
	 * @param class-string<T> $parentClass the parent type that the detail belongs to
	 * @param string $name aliases to identify the detail with respect ot the parent
	 * @param Closure(T): Info|null $childGetter
	 */
	public function addDetails(array $names, Closure $childGetter) : void{
		[$parentClass, $returnType] = self::validateClosure($childGetter);

		if(!isset($this->graph[$parentClass])){
			$this->graph[$parentClass] = [];
		}

		foreach($names as $name){
			if(strpos($name, " ") !== false) {
				throw new InvalidArgumentException("Spaces are not allowed in detail names");
			}

			$pieces = explode(".", $name);
			for($i = count($pieces) - 1; $i >= 0; $i--){
				$suffix = implode(".", array_slice($pieces, $i));
				if(!isset($this->graph[$parentClass][$suffix])){ // earlier-registered details get the priority
					$this->graph[$parentClass][$suffix] = $childGetter;
				}
			}
		}

		if(!isset($this->principalGraph[$parentClass])){
			$this->principalGraph[$parentClass] = [];
		}
		$this->principalGraph[$parentClass][] = new PrincipalDetail($names, $childGetter);
	}

	/**
	 * Allows an Info class to fallback to an alternative Info for resolution.
	 *
	 * The only parameter of `$fallbackGetter` is the "source class",
	 * and the return type of `$fallbackGetter` is the "destination class".
	 * When normal detail resolution (through `addDetail`) failed for an info with the "source class",
	 * the closure is called, and the context is changed to the "destination class" instead.
	 *
	 * This fallback mechanism is transitive,
	 * i.e. failing to resolve a fallback would lead to resolving other fallbacks.
	 *
	 * This mechanism is similar to making the source class *extend* the destination class,
	 * For example, `PlayerInfo` has a fallback result of `PositionInfo`.
	 *
	 * Therefore, beware infinite recursion. This should only be used as a "shortcut" of addDetail()
	 * that does not require an intermediate name.
	 *
	 * It is not advised to call `addFallback` if your module declared neither the base nor the fallback class
	 * to prevent infinite recursion.
	 *
	 * @template T of Info
	 * @template U of Info
	 * @param string  $baseClass
	 * @param Closure(T) -> U $fallbackGetter Given an instance of $baseClass, return an Info object, or null if not available
	 */
	public function addFallback(Closure $fallbackGetter) : void{
		if(!isset($this->aliasMap[$baseClass])){
			$this->aliasMap[$baseClass] = [];
		}
		$this->aliasMap[$baseClass][] = $fallbackGetter;
	}

	private static function validateClosure(Closure $closure) : void{
		$refl = new ReflectionFunction($closure);

		$params = $refl->getParameters();
		if(count($params) !== 1){
			throw new RuntimeException("The closure must declare exactly one parameter \$info");
		}
		$param = $params[0]->getClass();
		if($param === null){
			throw new RuntimeException("The first parameter of the closure must have a type (the Info subclass for the detail)");
		}
		self::validateClass($param, "closure parameter");

		$ret = $refl->getReturnType();
		if(!($ret instanceof ReflectionNamedType)){
			throw new RuntimeException("The closure must declare a return type");
		}
		self::validateClass($ret->getName(), "closure return type");

		return [$param, $ret->getName()];
	}

	private static function validateClass(string $class, string $context) : void{
		if(!class_exists($class)){
			throw new RuntimeException("Class $class does not exist. Check plugin dependency issues.");
		}

		if(!is_subclass_of($class, Info::class)){
			throw new RuntimeException(sprintf("Class %s does not extend %s", $class, Info::class));
		}

		if($class !== Info::class){
			throw new RuntimeException("The $context must specify the exact subclass of Info instead of just Info");
		}

		$refl = new ReflectionClass($class);
		if($refl->getParentClass()->getName() !== Info::class){
			throw new RuntimeException(sprintf("%s should extend %s directly instead of %s", $class, Info::class, $refl->getParentClass()->getName()));
		}
	}

	/**
	 * Resolves a path provided by user with respect to `$info`.
	 *
	 * @param string[] $tokens the info components
	 * @param Info $info the context to resolve in
	 * @param bool $recursive internal parameter, used to prevent resolving dynamic in fallbacks
	 * @return string|null the string resolved, or null if an error occurred
	 */
	public function resolve(array $tokens, Info $info, bool $recursive = false) : ?string{
		if(count($tokens) === 0){
			return $info->toString();
		}

		if(isset($this->graph[$class = get_class($info)])){
			if(isset($this->graph[$class][$tokens[0]])){
				$delegate = $closure($info);
				if($delegate !== null){
					$result = $this->resolve(array_slice($tokens, 1), $delegate);
					if($result !== null){
						return $result;
					}
				}
			}
		}

		if(isset($this->aliasMap[$class])){
			// TODO change resolve() signature to allow BFS fallback
			// instead of DFS traversal into a single fallback sequence

			$closures = $this->aliasMap[$class];
			foreach($closures as $closure){
				/** @var Info|null $delegate */
				$delegate = $closure($info);
				if($delegate !== null){
					$result = $this->resolve($tokens, $delegate, true);
					if($result !== null){
						return $result;
					}
				}
			}
		}

		if(!$recursive && $info instanceof DynamicInfo){
			$delegate = $info->resolveDynamic($tokens[0]);
			if($delegate !== null){
				$result = $this->resolve(array_slice($tokens, 1), $delegate);
			}
		}

		return null;
	}

	/**
	 * Lists the details available in the `Info`,
	 * yielding the full detail name as the key and the resolved value as the value.
	 *
	 * Aliases (names passed to `addDetails` other than the first one) * will not be yielded,
	 * so the number of items yielded in `listDetails` should be equal to
	 * the number of times `addDetail`/`addDetails` is called.
	 *
	 * Details from fallbacks are also yielded.
	 *
	 * The order of return values is *undefined*.
	 * Consider using `ksort(iterator_to_array(listDetails))` if sorting is required.
	 *
	 * @param Info $info
	 * @return Generator<string, array{PrincipalDetail, Info}, null, void}>
	 */
	public function listDetails(Info $info) : Generator{
		if(isset($this->principalGraph[$class = get_class($info)])){
			$details = $this->principalGraph[$class];
			foreach($details as $detail){
				$delegate = $detail->getClosure()($info);
				if($delegate !== null){
					yield $detail->getIdentifiers()[0] => [$detail, $delegate];
				}
			}
		}

		if(isset($this->aliasMap[$class])){
			foreach($this->aliasMap[$class] as $closure){
				$delegate = $closure($info);
				if($delegate !== null){
					yield from $this->listDetails($delegate);
				}
			}
		}
	}

	/**
	 * This method returns the same values as `listDetails`,
	 * but with detail names minified to just as long as needed.
	 *
	 * For example, if `listDetails` yields keys `a.c`, `b.c` and `a.d`,
	 * the keys returned by `listMinifiedDetails` are `a.c`, `b.c` and `d`
	 * (because `d` has no ambiguity).
	 *
	 * @param Info $info
	 *
	 * @return PrincipalDetail[]
	 */
	public function listMinifiedDetails(Info $info) : array{
		$array = iterator_to_array($this->listDetails($info));
		$tree = new NameTree;
		foreach($this->listDetails($info) as $id => $value){
			$tree->insert(explode(".", $id), $value);
		}
		return $tree->getResults();
	}
}
