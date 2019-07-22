<?php

/*
 * InfoAPI
 *
 * Copyright (C) 2019 SOFe
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
use function array_slice;
use function count;
use function explode;
use function get_class;
use function implode;
use function strpos;
use function substr_count;

class InfoRegistry{
	/** @var InfoRegistry */
	private static $instance;

	public static function getInstance() : InfoRegistry{
		if(self::$instance === null){
			self::$instance = new InfoRegistry();
		}
		return self::$instance;
	}

	/** @var Closure[][] (ParentInfo::class) => [ (full.info.name) => (function(ParentInfo) : ?ChildInfo) ] */
	private $graph = [];

	/** @var PrincipalDetail[][] (ParentInfo::class) => PrincipalDetail[] */
	private $principalGraph = [];

	/** @var Closure[][] (BaseInfo::class) => [ (function(BaseInfo) : ?FallbackInfo) ] */
	private $aliasMap = [];

	private function __construct(){
		BlockInfo::register($this);
		WorldInfo::register($this);
		NumberInfo::register($this);
		PlayerInfo::register($this);
		PositionInfo::register($this);
		RatioInfo::register($this);
		StringInfo::register($this);
	}

	public function addDetail(string $parentClass, string $name, Closure $childGetter) : void{
		$this->addDetails($parentClass, [$name], $childGetter);
	}

	public function addDetails(string $parentClass, array $names, Closure $childGetter) : void{
		if(!isset($this->graph[$parentClass])){
			$this->graph[$parentClass] = [];
		}
		foreach($names as $name){
			$pieces = explode(".", $name);
			$suffix = null;
			for($i = count($pieces) - 1; $i >= 0; $i--){
				$suffix = $suffix !== null ? "{$pieces[$i]}.$suffix" : $pieces[$i];
				if(!isset($this->graph[$parentClass][$suffix])){
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
	 * Resolving instances of $baseClass will resort to resolving the result of $fallbackGetter on failure.
	 * This is equivalent to making $baseClass extend the result of $fallbackGetter
	 * such that calling $baseClass might call the result of $fallbackGetter too.
	 *
	 * Be aware of infinite recursion. This should only be used as a "shortcut" of addDetail()
	 * that does not require an intermediate name.
	 *
	 * @param string  $baseClass
	 * @param Closure $fallbackGetter Given an instance of $baseClass, return an Info object, or null if not available
	 */
	public function addFallback(string $baseClass, Closure $fallbackGetter) : void{
		if(!isset($this->aliasMap[$baseClass])){
			$this->aliasMap[$baseClass] = [];
		}
		$this->aliasMap[] = $fallbackGetter;
	}

	public function resolve(array $tokens, Info $info) : ?string{
		if(isset($this->graph[$class = get_class($info)])){
			foreach($this->graph[$class] as $name => $closure){
				if(strpos($tokens . " ", $name . " ") === 0){
					/** @var Info|null $delegate */
					$delegate = $closure($info);
					if($delegate !== null){
						$result = $this->resolve(array_slice($tokens, substr_count($name, " ") + 1), $delegate);
						if($result !== null){
							return $result;
						}
					}
				}
			}
		}
		if(isset($this->aliasMap[$class])){
			$closures = $this->aliasMap[$class];
			foreach($closures as $closure){
				/** @var Info|null $delegate */
				$delegate = $closure($info);
				if($delegate !== null){
					$result = $this->resolve($tokens, $delegate);
					if($result !== null){
						return $result;
					}
				}
			}
		}
		return null;
	}

    /**
     * @param Info $info
     * @return Generator|Info[]
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
	 * @param Info $info
	 *
	 * @return PrincipalDetail[]
	 */
	public function listMinifiedDetails(Info $info) : array{
		// TODO unit testing required
		/** @var string[][]|PrincipalDetail[][]|Info[][] $list */
		$list = [];
		$used = [];
		foreach($this->listDetails($info) as $identifier => [$detail, $info]){
			$parts = explode(".", $identifier);
			for($i = count($parts) - 1; $i >= 0; $i--){
				$joined = implode(".", array_slice($parts, $i));
				if(isset($used[$joined])){
					$collisionIndex = $used[$joined];
					/** @var string $thatName */
					/** @var PrincipalDetail $thatDetail */
					[&$thatName, $thatDetail] = $list[$collisionIndex];
					if($thatName !== $joined){
						continue;
					}

					/** @noinspection PhpUnusedLocalVariableInspection */
					$thatName = $thatDetail->increasePrecision($joined);
					$used[$thatName] = $collisionIndex;

					continue;
				}

				$used[$joined] = count($list);
				$list[] = [$joined, $detail, $info];
				$added = true;
				break;
			}
            /** @noinspection PhpStatementHasEmptyBodyInspection (remove when done) */
            if(!isset($added)){
				// TODO warning: one info is completely masked by another
			}
		}

		$output = [];
		foreach($list as [$name, $detail, $info]){
			$output[$name] = [$detail, $info];
		}
		return $output;
	}
}
