<?php

declare(strict_types=1);

/**
 * Interfaces for standard kinds shared between instances of the shaded virion.
 */
namespace Shared\SOFe\InfoAPI\Standard;

/** A node that represents a string. */
interface StringNode {
	/** Kind for string nodes. Only nodes that implement StringNode should return this kind. */
	public const KIND = "infoapi/shared/string";

	/** Returns the string represented by this node. */
	public function get() : string;
}

/** A node that represents an integer. */
interface IntNode {
	/** Kind for string nodes. Only nodes that implement IntNode should return this kind. */
	public const KIND = "infoapi/shared/integer";

	/** Returns the integer represented by this node. */
	public function get() : int;
}

/** A node that represents a float. */
interface FloatNode {
	/** Kind for float nodes. Only nodes that implement FloatNode should return this kind. */
	public const KIND = "infoapi/shared/float";

	/** Returns the float represented by this node. */
	public function get() : float;
}

/** A node that represents a bool. */
interface BoolNode {
	/** Kind for bool nodes. Only nodes that implement BoolNode should return this kind. */
	public const KIND = "infoapi/shared/bool";

	/** Returns the bool represented by this node. */
	public function get() : bool;
}
