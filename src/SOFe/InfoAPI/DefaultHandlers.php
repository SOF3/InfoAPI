<?php

namespace SOFe\InfoAPI;

use pocketmine\event\Listener;
use pocketmine\Player;

final class DefaultHandlers implements Listener{
	public function e(InfoResolveEvent $event){
		if($event->getInfo() instanceof PlayerInfo){
			$this->providePlayer($event->getInfo()->getPlayer(), $event);
		}
	}
	
	private function providePlayer(Player $player, InfoResolveEvent $event) : void{
		if($event->matches("name")){
			$event->resolve(new StringInfo($player->getName()));
			return;
		}

		if($event->matchAny([
			"nametag",
			"name tag"
		], function() use($player) : Info{
			return new StringInfo($player->getNameTag());
		})){
			return;
		}

		if($event->matches("ip")){
			$event->resolve(new StringInfo($player->getAddress()));
			return;
		}
	}
}
