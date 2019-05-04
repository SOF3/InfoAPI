<?php

namespace SOFe\InfoAPI;

use function array_slice;
use function count;
use function strlen;
use function strtolower;
use function substr;
use pocketmine\event\Cancellable;
use pocketmine\event\Event;

class InfoResolveEvent extends Event implements Cancellable{
	private $tokens;
	private $info;
	private $residue;
	private $result = null;

	public function __construct(array $tokens, Info $info){
		$this->tokens = $tokens;
		$this->info = $info;
	}

	public function peek(int $size) : array{
		return array_slice($this->tokens, 0, $size);
	}

	/**
	 * @param string[][] $matches
	 * @param callable $resolve (string[] $match) => Info
	 * @return bool whether the event is resolved
	 */
	public function matchAny(array $matches, callable $resolve) : bool{
		foreach($matches as $match){
			if($this->matches(...$match)){
				$this->resolve($resolve($match), count($match));
				return true;
			}
		}
		return false;
	}

	public function matches(string ...$tokens) : bool{
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

	public function getResidue() : array{
		return $this->residue;
	}

	public function getResult() : ?Info{
		return $this->result;
	}

	private static function endsWith(string $string, string $suffix){
		return substr($string, -strlen($suffix)) === $suffix;
	}
}
