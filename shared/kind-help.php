<?php

declare(strict_types=1);

namespace Shared\SOFe\InfoAPI;

/**
 * Help message for a kind.
 */
final class KindHelp {
	/** @var ?Registry<KindHelp> */
	public static ?Registry $global = null;

	public function __construct(
		/** The kind that this object describes */
		public string $kind,

		/** Help message for the kind. */
		public string $help,
	) {
	}
}
