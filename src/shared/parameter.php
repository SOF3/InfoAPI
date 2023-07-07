<?php

declare(strict_types=1);

namespace Shared\SOFe\InfoAPI\Parameter;

/**
 * Defines a parameter required for a mapping.
 */
final class Parameter {
	/** The name of the parameter. */
	public string $name;

	/** The type of the parameter. */
	public NodeType|StringType|IntType|FloatType|BoolType|ListType $type;
}

/**
 * Type of a parameter, affecting how an argument is parsed.
 * Sealed interface not to be implemented.
 */
interface Type {
}

/** The parameter is parsed as a node expression. */
final class NodeType implements Type {
	/** The expected kind of the node. */
	public string $kind;
}

/** The parameter is parsed as a JSON string. */
final class StringType implements Type {
}

/** The parameter is parsed as an integer /-?[0-9]/. */
final class IntType implements Type {
}

/** The parameter is parsed as a float. Only finite values /-?\d(\.\d+)?(e[+-]\d+)/ are accepted. */
final class FloatType implements Type {
}

/** No parameter value is expected. The argument reports whether the parameter exists. */
final class BoolType implements Type {
}

/**
 * The parameter is parsed as one of the following ways:
 *
 * - If the parameter is passed multiple times, they are merged into one array.
 * - If the parameter starts with a `[`, it parses values delimited by `,` until a `]` is encountered.
 */
final class ListType implements Type {
	/** The parameter type of each item. */
	public NodeType|StringType|IntType|FloatType|BoolType|ListType $item;
}
