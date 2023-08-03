<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use pocketmine\command\CommandSender;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\KindHelp;

/**
 * @extends Index<Display>
 */
final class DisplayIndex extends Index {
	/** @var array<string, Display> */
	private array $kinds;

	public function reset() : void {
		$this->kinds = [];
	}

	public function index($display) : void {
		$this->kinds[$display->kind] = $display;
	}

	public function getDisplay(string $kind) : ?Display {
		$this->sync();

		if (isset($this->kinds[$kind])) {
			$display = $this->kinds[$kind];
			return $display;
		} else {
			return null;
		}
	}

	public function display(string $kind, mixed $value, ?CommandSender $sender) : string {
		$display = $this->getDisplay($kind);
		return $display !== null ? ($display->display)($value, $sender) : Display::INVALID;
	}

	public function canDisplay(string $kind) : bool {
		$this->sync();

		return isset($this->kinds[$kind]);
	}
}

/**
 * @extends Index<KindHelp>
 */
final class KindHelpIndex extends Index {
	/** @var array<string, KindHelp> */
	private array $kinds;

	public function reset() : void {
		$this->kinds = [];
	}

	public function index($help) : void {
		$this->kinds[$help->kind] = $help;
	}

	public function get(string $kind) : ?string {
		$this->sync();

		if (!isset($this->kinds[$kind])) {
			return null;
		}

		return $this->kinds[$kind]->help;
	}
}
