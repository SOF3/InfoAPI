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

use UnexpectedValueException;

/**
 * This is a convenient class to be passed into InfoAPI::resolve() directly.
 * This Info must never be returned by an info resolver (because it doesn't implement a toString),
 * but it could be used as the basic context.
 */
class ContextInfo extends Info{
	/** @var array|Info[] */
	private $infos;

	/**
	 * @param Info[] $infos
	 */
	public function __construct(array $infos){
		$this->infos = $infos;
	}

	/**
	 * @return Info[]
	 */
	public function getInfos() : array{
		return $this->infos;
	}

	public function defaults(InfoResolveEvent $event) : bool{
		foreach($this->infos as $name => $value){
			if($event->match($name, static function() use ($value) : Info{
				return $value;
			})){
				return true;
			}
		}
		return false;
	}

	public function toString() : string{
		throw new UnexpectedValueException("ContextInfo must not be returned by an info resolver");
		// Since we can't have an empty info iden, this would never get called unless returned by an info resolver.
	}
}
