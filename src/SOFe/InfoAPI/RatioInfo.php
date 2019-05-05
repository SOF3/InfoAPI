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

use Generator;
use InvalidArgumentException;
use function gettype;
use function is_numeric;
use function sprintf;

class RatioInfo extends Info{
	/** @var float */
	private $value;
	/** @var float */
	private $max;

	public function __construct($value, $max){
		if(!is_numeric($value)){
			throw new InvalidArgumentException("Expected numeric value for \$value, got " . gettype($value));
		}
		if(!is_numeric($max)){
			throw new InvalidArgumentException("Expected numeric value for \$max, got " . gettype($max));
		}
		$this->value = (float) $value;
		$this->max = (float) $max;
	}

	public function getValue() : float{
		return $this->value;
	}

	public function getMax() : float{
		return $this->max;
	}

	public function toString() : string{
		return sprintf("%g / %g", $this->value, $this->max);
	}

	public function defaults(InfoResolveEvent $event) : void{
		$event->match("ratio.value", function() : Info{
			return new NumberInfo($this->value);
		}) || $event->match("ratio.max", function() : Info{
			return new NumberInfo($this->max);
		});
	}

	public function fallbackInfos() : Generator{
		yield new NumberInfo($this->value / $this->max);
	}
}
