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

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use function array_slice;
use function count;
use function explode;
use function strlen;
use function strtolower;
use function substr;

class InfoResolveEvent extends Event implements Cancellable{
	/** @var string[] */
	private $tokens;
	/** @var Info */
	private $info;
	/** @var string[] */
	private $residue;
	/** @var Info|null */
	private $result = null;

	/**
	 * @param string[] $tokens
	 * @param Info     $info
	 */
	public function __construct(array $tokens, Info $info){
		$this->tokens = $tokens;
		$this->info = $info;
	}

	public function getInfo() : Info{
		return $this->info;
	}

	public function peek(int $size) : array{
		return array_slice($this->tokens, 0, $size);
	}

	public function match(string $match, callable $resolve) : bool{
		return $this->matchAny([$match], $resolve);
	}

	/**
	 * @param string[] $matches
	 * @param callable $resolve (string[] $match) => Info
	 *
	 * @return bool whether the event is resolved
	 */
	public function matchAny(array $matches, callable $resolve) : bool{
		foreach($matches as $match){
			if($this->matches(explode(" ", $match))){
				$this->resolve($resolve($match), count($match));
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string[] $tokens
	 *
	 * @return bool
	 */
	private function matches(array $tokens) : bool{
		foreach($tokens as $i => $token){
			$token = strtolower($token);
			$other = strtolower($this->tokens[$i]);
			if($token !== $other && !self::endsWith($other, ".$token")){
				return false;
			}
		}
		return true;
	}

	public function resolve(Info $result, int $size = 1) : void{
		$this->setCancelled();
		$this->residue = array_slice($this->tokens, $size);
		$this->result = $result;
	}

	/**
	 * @return string[]
	 */
	public function getResidue() : array{
		return $this->residue;
	}

	public function getResult() : ?Info{
		return $this->result;
	}

	private static function endsWith(string $string, string $suffix){
		return substr($string, -strlen($suffix)) === $suffix;
	}

	public function call() : void{
		if($this->isCancelled()){
			return;
		}
		$this->info->defaults($this);
		if($this->isCancelled()){
			return;
		}
		/** @var Info $info */
		foreach($this->info->fallbackInfos() as $info){
			$event = new InfoResolveEvent($this->tokens, $info);
			$event->call();
			if($event->isCancelled()){
				$this->isCancelled();
				$this->residue = $event->residue;
				$this->result = $event->result;
				return;
			}
		}
	}
}
