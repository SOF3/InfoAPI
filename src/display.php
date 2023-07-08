<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use pocketmine\command\CommandSender;
use pocketmine\utils\SingletonTrait;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Registry;

/**
 * @extends Index<Display>
 */
final class DisplayIndex extends Index {
	use SingletonTrait;

	private static function make() : self {
		/** @var Registry<Display> $global */
		$global = RegistryImpl::getInstance(Display::$global);
		return new self([Defaults\Index::reusedDisplays(), $global]);
	}

	/** @var array<string, Display> */
	private array $kinds;

	public function reset() : void {
		$this->kinds = [];
	}

	public function index($display) : void {
		$this->kinds[$display->kind] = $display;
	}

	public function display(string $kind, mixed $value, CommandSender $sender) : string {
		$this->sync();
		if (isset($this->kinds[$kind])) {
			$display = $this->kinds[$kind];
			return ($display->display)($value, $sender);
		} else {
			return Display::INVALID;
		}
	}
}
