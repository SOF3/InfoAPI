<?php

declare(strict_types=1);

namespace Shared\SOFe\InfoAPI;

use Shared\SOFe\InfoAPI\Parameter\Parameter;

/**
 * An object that represents a value or a context that can resolve to other values.
 */
interface Node {
	/**
	 * The kind of a node defines the set of other nodes it can be mapped to/from.
	 *
	 * The kind is typically the type of the backing object.
	 */
	public function getKind() : string;
}

/**
 * A registry that collects and serves the mapping between nodes provided by different plugins.
 */
interface Registry {
	/**
	 * Adds a mapping to this registry.
	 */
	public function provideMapping(Mapping $mapping) : void;

	/**
	 * Returns a number that increases every time the registry is mutated.
	 */
	public function getGeneration() : int;

	/**
	 * Returns all Mappings known by this registry.
	 *
	 * @return Mapping[] A linear list of Mapping objects.
	 */
	public function getAllMappings() : array;
}

/**
 * A class that holds a global instance of Registry.
 *
 * Since the API of Registry is very simple,
 * it is expected that the implementation would almost never change.
 */
final class GlobalRegistrySingleton {
	public static ?Registry $global = null;
}

/**
 * A mapping is the conversion from one node to another node.
 *
 * A mapping could accept parameters during mapping.
 */
interface Mapping {
	/**
	 * The separator for imploding/exploding a fully-qualified name.
	 */
	public const FQN_SEPARATOR = ":";

	/**
	 * The fully-qualified name of the mapping.
	 *
	 * A fully-qualified name is a linear list of strings,
	 * starting with the highest-level namespaces and ending with the short name.
	 * A fully-qualified name should be matched by any subsequence of the linear list that ends with the same short name.
	 * Each component of the fully-qualified name must not contain a `:`.
	 *
	 * A fully-qualified name must be unique among all mappings for the same source kind.
	 *
	 * @return string[]
	 */
	public function getFullyQualifiedName() : array;

	/**
	 * The node kind that the mapping accepts.
	 */
	public function getSourceKind() : string;

	/**
	 * The node kind that the mapping emits.
	 */
	public function getTargetKind() : string;

	/**
	 * Whether this mapping can be automatically executed if no matches are found.
	 * If this returns true, `getParameters()` must return an empty array.
	 */
	public function canImplicit() : bool;

	/**
	 * Lists the parameters that the mapping accepts.
	 *
	 * @return Parameter[] A linear list of all parameters.
	 */
	public function getParameters() : array;

	/**
	 * Converts the input node to an output node.
	 *
	 * @param mixed[] $args The arguments in the same order as parameters if any. Empty array otherwise.
	 *
	 * @return ?Node Returns null if incompatible.
	 */
	public function map(Node $input, array $args) : ?Node;
}
