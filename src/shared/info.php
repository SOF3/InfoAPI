<?php

declare(strict_types=1);

namespace Shared\SOFe\InfoAPI;

use pocketmine\command\CommandSender;

/**
 * A node that can be displayed.
 */
interface Info extends Node {
	public function display(CommandSender $target) : string;
}
