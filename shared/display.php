<?php

declare(strict_types=1);

namespace Shared\SOFe\InfoAPI;

use Closure;
use pocketmine\command\CommandSender;

/**
 * Describes how to display an info for template string.
 */
final class Display {
	/** Conventional placeholder for invalid values. This is for display only and has no special semantics. */
	public const INVALID = "{invalid value}";

	/** @var ?Registry<Display> */
	public static ?Registry $global = null;

	public function __construct(
		/** The kind that this display object describes */
		public string $kind,

		/**
		 * Displays a value for a template.
		 *
		 * @var Closure(mixed $value, ?CommandSender $sender): string
		 */
		public Closure $display,
	) {
	}
}
