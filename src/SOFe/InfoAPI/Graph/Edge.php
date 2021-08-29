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

namespace SOFe\InfoAPI\Graph;

use SOFe\InfoAPI\Ast\ChildName;

/**
 * An instance of relationship between two nodes.
 */
final class Edge {
	/**
	 * The fully-qualified name if the edge is a parent-child relationship.
	 */
	private ?ChildName $name;

	private function __construct(?ChildName $name) {
		$this->name = $name;
	}

	public function isFallback() : bool {
		return $this->name === null;
	}

	/**
	 * Returns the info name used to resolve parent-child relationships.
	 */
	public function getName() : ?ChildName {
		return $this->name;
	}

	/**
	 * Adds the weight of this edge to a path.
	 */
	public function addWeight(EdgeWeight $weight) : void {
		if($this->isFallback()) {
			++$weight->fallback;
		} else {
			++$weight->parentChild;
		}
	}

	/**
	 * Creates a parent-child info edge.
	 */
	static public function parentChild(ChildName $name) : self {
		return new self($name);
	}

	/**
	 * Creates a fallback info edge.
	 */
	static public function fallback() : self {
		return new self(null);
	}
}
