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

use pocketmine\block\Block;

final class BlockTypeInfo extends Info {
	private Block $value;

	public function __construct(Block $value) {
		$this->value = $value;
	}

	public function getValue() : Block {
		return $this->value;
	}

	public function toString() : string {
		return $this->value->getName();
	}

	static public function getInfoType() : string {
		return "block type";
	}

	static public function init(?InfoAPI $api) : void {
		InfoAPI::provideInfo(self::class, StringInfo::class, "infoapi.block.name",
			fn($info) => $info->getValue()->getName());
	}
}
