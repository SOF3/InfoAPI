<?php

namespace SOFe\InfoAPI;

class StringInfo implements Info{
	private $string;

	public function __construct(string $string){
		$this->string = $string;
	}

	public function toString() : string{
		return $this->string;
	}
}
