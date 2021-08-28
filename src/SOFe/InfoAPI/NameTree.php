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

use array_pop;

/**
 * A data structure to incrementally lengthen keys upon collision.
 *
 * Due to practical reasons, the keys are only dot-delimited.
 *
 * This is a port of https://github.com/SOF3/octorest/blob/2907ed79c0f584102d53c806dad6116d182d8cf1/build/src/gen/tree.rs
 *
 * @template T
 */
final class NameTree{
	/** @var array<string, array{residue: string[], value: T}|null> */
	private $map = [];

	/**
	 * @param string[] $parts the name parts to use, always selecting a suffix.
	 * @param T $value the value to associate with the name parts
	 */
	public function insert(array $parts, $value) : void{
		$key = array_pop($parts);

		while(isset($this->map[$key])){
			if($this->map[$key] !== null){
				$otherEntry = $this->map[$key];
				$this->map[$key] = null;

				$move = array_pop($otherEntry["residue"]);
				if($move === null){
					// collision detected, let's drop the new value.
					return;
				}
				$otherKey = "$move.$key";
				$this->map[$otherKey] = $otherEntry;
			}

			$move = array_pop($parts);
			if($move === null){
				// collision detected, let's drop the new value.
				return;
			}
			$key = "$move.$key";
		}

		$this->map[$key] = [
			"residue" => $parts,
			"value" => $value,
		];
	}

	/**
	 * @return array<string, T>
	 */
	public function getResults() : array{
		$output = [];
		foreach($this->map as $k => $v){
			if($v !== null){
				$output[$k] = $v["value"];
			}
		}
		return $output;
	}
}
