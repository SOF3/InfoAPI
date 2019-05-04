<?php

namespace SOFe\InfoAPI;

use pocketmine\plugin\PluginBase;

class Main extends PluginBase{
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents(new DefaultHandlers, $this);
	}
}
