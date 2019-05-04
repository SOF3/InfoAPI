<?php

namespace SOFe\InfoAPI;

use pocketmine\Player;

class PlayerInfo implements Info{
	public function __construct(Player $player){
		$this->player = $player;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function toString() : string{
		return $this->player->getName();
	}
}
