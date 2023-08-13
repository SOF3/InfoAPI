<?php

declare(strict_types=1);

namespace Shared\SOFe\InfoAPI;

/**
 * Help message for a kind.
 */
final class KindMeta {
	/** @var ?Registry<KindMeta> */
	public static ?Registry $global = null;

	public function __construct(
		/** The kind that this object describes */
		public string $kind,

		/** A short, human-readable name for this type */
		public ?string $shortName,

		/** Help message for the kind. */
		public ?string $help,

		/**
		 * Additional non-standard metadata to describe this kind.
		 *
		 * @var array<string, mixed>
		 */
		public array $metadata,
	) {
	}
}
