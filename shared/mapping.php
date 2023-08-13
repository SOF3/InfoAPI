<?php

declare(strict_types=1);

namespace Shared\SOFe\InfoAPI;

use Closure;
use Generator;

/**
 * A mapping is the conversion from one value of a known kind to another value of a fixed kind.
 *
 * A mapping could accept parameters during mapping.
 */
final class Mapping {
	/**
	 * A singleton field to track all instances of Mapping.
	 * @var ?Registry<Mapping>
	 */
	public static ?Registry $global = null;

	/** The separator for imploding/exploding a fully-qualified name. */
	public const FQN_SEPARATOR = ":";

	/** The regex charset that tokens must match. */
	public const FQN_TOKEN_REGEX_CHARSET = "A-Za-z0-9_-";

	public function __construct(
		/**
		 * The fully-qualified name of the mapping.
		 *
		 * A fully-qualified name is a linear list of strings,
		 * starting with the highest-level namespaces and ending with the short name.
		 * A fully-qualified name should be matched by any subsequence of the linear list that ends with the same short name.
		 * Each component of the fully-qualified name must match /[A-Za-z0-9_\-]/ and must not be `true` or `false`.
		 *
		 * A fully-qualified name must be unique among all mappings for the same source kind.
		 *
		 * @var string[]
		 */
		public array $qualifiedName,

		/** The kind that the mapping accepts. */
		public string $sourceKind,

		/** The kind that the mapping emits. */
		public string $targetKind,

		/**
		 * Whether this mapping can be automatically executed if no matches are found.
		 *
		 * If this is true, $parameters must be empty.
		 */
		public bool $isImplicit,

		/**
		 * A linear list of all parameters that the mapping accepts.
		 *
		 * @var Parameter[]
		 */
		public array $parameters,

		/**
		 * Converts the input value.
		 *
		 * The $source parameter is a value reported to be the source kind.
		 * The $args parameter accepts the arguments for a mapping operation in the same order as $parameters.
		 * The closure should return a value of the target kind.
		 *
		 * The closure should validate the types of source and args.
		 * Invalid inputs should result in an invalid output.
		 * A null value is not necessarily short-circuited by the framework.
		 * The `ReflectUtil` class short-circuits a single mapping to return null if inputs are invalid,
		 * but subsequent mappings are still executed based on null.
		 * A null output may be used for coalescence in tepmlating.
		 *
		 * @var Closure(mixed $source, mixed[] $args): mixed
		 */
		public Closure $map,

		/**
		 * Watches the inputs for changes.
		 *
		 * The closure has the same inputs as $map.
		 * It returns a generator that implements the await-generator protocol with the Traverser extension.
		 * It should traverse arbitrary values to indicate a change in the mapped value.
		 * The traversed value is not handled by the framework.
		 *
		 * If the target kind is also a mutable object,
		 * a "change" is defined as the representation of a fundamentally different object
		 * that would lead to different subscription in transitive mappings from the mapped value.
		 * For example, if the target kind is a player,
		 * a "change" should only be indicated when the mapping points to a different player,
		 * but not when information of the player itself (e.g. player location) changes.
		 * Detailed semantics could be further specified by the definition of the target kind,
		 * but in general, "change" does not imply and is not implied by a `!==` in the returned value.
		 *
		 * @var ?Closure(mixed $source, mixed[] $args): Generator
		 */
		public ?Closure $subscribe,

		/**
		 * Help message of this mapping.
		 *
		 * Used in info discovery and documentation.
		 */
		public string $help,

		/**
		 * Additional non-standard metadata to describe this mapping.
		 *
		 * @var array<string, mixed>
		 */
		public array $metadata,
	) {
	}
}

/**
 * Defines a parameter required for a mapping.
 */
final class Parameter {
	public function __construct(
		/** The name of the parameter. */
		public string $name,

		/**
		 * The kind of the parameter info.
		 * Parameters of primitive types may accept literal expressions too.
		 */
		public string $kind,

		/** Whether this parameter can be required multiple times. */
		public bool $multi,

		/** Whether this parameter is optional. */
		public bool $optional,

		/**
		 * Additional non-standard metadata to describe this mapping.
		 *
		 * @var array<string, mixed>
		 */
		public array $metadata,
	) {
	}
}
