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

use Closure;
use SOFe\InfoAPI\Ast\ChildName;
use SOFe\InfoAPI\Info;

/**
 * An instance of relationship between two nodes.
 */
final class Edge {
	/**
	 * The fully-qualified name if the edge is a parent-child relationship.
	 */
	private ?ChildName $name;

	/** @phpstan-var Closure(Info): ?Info */
	private Closure $resolver;

	/** @phpstan-var array<string, string> */
	private $metadata = [];

	private function __construct(?ChildName $name, Closure $resolver) {
		$this->name = $name;
		$this->resolver = $resolver;
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
	 * A closure that maps the source info to the dest info.
	 *
	 * @phpstan-return Closure(Info): ?Info
	 */
	public function getResolver() : Closure {
		return $this->resolver;
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
	 * Attaches a metadata string to this edge.
	 */
	public function setMetadata(string $key, string $value) : self {
		$this->metadata[$key] = $value;
		return $this;
	}

	/**
	 * Returns a metadata string from this edge.
	 */
	public function getMetadata(string $key) : ?string {
		return $this->metadata[$key] ?? null;
	}

	/**
	 * Creates a parent-child info edge.
	 *
	 * @phpstan-param Closure(Info): ?Info $resolver
	 */
	static public function parentChild(ChildName $name, Closure $resolver) : self {
		return new self($name, $resolver);
	}

	/**
	 * Creates a fallback info edge.
	 *
	 * @phpstan-param Closure(Info): ?Info $resolver
	 */
	static public function fallback(Closure $resolver) : self {
		return new self(null, $resolver);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __debugInfo() : array {
		return [
			"name" => $this->name,
			"metadata" => $this->metadata,
		];
	}
}
