<?php

declare(strict_types=1);

namespace Shared\SOFe\InfoAPI;

/**
 * Hints that an object type always belongs to a certain kind.
 */
final class ReflectHint {
	/**
	 * A singleton field to track all instances of ReflectHint.
	 * @var ?Registry<Mapping>
	 */
	public static ?Registry $global = null;

	public function __construct(
		/** @var class-string The object class */
		public string $class,
		/** @var string The kind of this class */
		public string $kind,
	) {
	}
}
