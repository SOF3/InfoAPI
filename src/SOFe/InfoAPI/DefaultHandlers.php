<?php

namespace SOFe\InfoAPI;

use pocketmine\event\Listener;
use pocketmine\Player;

final class DefaultHandlers implements Listener{
	public function e(InfoResolveEvent $event){
		$info = $event->getInfo();
		if($info instanceof PlayerInfo){
			$this->providePlayer($info->getPlayer(), $event);
		}
	}
	
	private function providePlayer(Player $player, InfoResolveEvent $event) : void{
		if($event->matches("pocketmine.name")){
			$event->resolve(new StringInfo($player->getName()));
			return;
		}

		if($event->matchAny([
			"pocketmine.nametag",
			"pocketmine.name tag"
		], static function() use ($player) : Info{
			return new StringInfo($player->getNameTag());
		})){
			return;
		}

		if($event->matches("pocketmine.ip")){
			$event->resolve(new StringInfo($player->getAddress()));
			return;
		}
	}
}
