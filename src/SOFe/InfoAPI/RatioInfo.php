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

final class RatioInfo extends Info {
	private float $current;
	private float $max;

	public function __construct(float $current, float $max) {
		$this->current = $current;
		$this->max = $max;
	}

	public function getCurrent() : float {
		return $this->current;
	}

	public function getMax() : float {
		return $this->max;
	}

	public function toString() : string {
		return sprintf("%g/%g", $this->current, $this->max);
	}

	public function getInfoType() : string {
		return "proportion";
	}

	static public function init(?InfoAPI $api) : void {
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.ratio.current",
			fn($info) => new NumberInfo($info->getCurrent()),
			$api);
		InfoAPI::provideInfo(self::class, NumberInfo::class, "infoapi.ratio.max",
			fn($info) => new NumberInfo($info->getMax()),
			$api);
		InfoAPI::provideFallback(self::class, NumberInfo::class,
			fn($info) => $info->getMax() !== 0.0 ? new NumberInfo($info->getCurrent() / $info->getMax()) : null,
			$api);
	}
}
