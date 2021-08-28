<?php

/*
 * InfoAPI
 *
 * Copyright (C) 2019-2021 SOFe
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace SOFe\InfoAPI\ui;

use Closure;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use Generator;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;
use SOFe\InfoAPI\Info;
use SOFe\InfoAPI\InfoRegistry;
use SOFe\InfoAPI\PlayerInfo;
use function array_merge;
use function implode;
use function strlen;
use function substr;

class BrowseInfosCommand extends Command implements PluginIdentifiableCommand{
	/** @var Plugin */
	private $plugin;

	public function __construct(Plugin $plugin){
		parent::__construct("info", "Browse available infos", "/info [player name]");
		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(isset($args[0])){
			$target = $this->plugin->getServer()->getPlayer($args[0]);
			if($target === null){
				$sender->sendMessage("Player not found: $args[0]");
				throw new InvalidCommandSyntaxException("");
			}
		}else{
			$target = $sender;
			if(!($target instanceof Player)){
				$sender->sendMessage("A player argument must be provided when not executed in-game");
				throw new InvalidCommandSyntaxException("");
			}
		}

		/** @var Player $target */
		$info = new PlayerInfo($target);
		Await::g2c($this->showInfo(InfoRegistry::getInstance(), [], $info, $sender), null, [BrowseCancelledException::class => function(){}]);
	}

	private function showInfo(InfoRegistry $registry, array $stack, Info $parent, CommandSender $sender) : Generator{
		$title = implode(" ", $stack);
		$prefix = empty($title) ? "" : "$title ";
		$value = $parent->toString();
		$details = [];
		foreach($registry->listMinifiedDetails($parent) as $name => [$detail, $info]){
			$optional = substr($detail->getIdentifiers()[0], 0, strlen($detail->getIdentifiers()[0]) - strlen($name));
			/** @var Info $child */
			$child = $detail->getClosure()($parent);
			$value = $child->toString();
			$details[] = ["optional" => $optional, "required" => $name, "info" => $child, "value" => $value];
		}
		while(true){
			if($sender instanceof Player){
				$form = $this->createInfoMenu($title, $value, $prefix, $details,
					Closure::fromCallable(yield Await::RESOLVE), Closure::fromCallable(yield Await::REJECT));
				$sender->sendForm($form);
				$choice = yield Await::ONCE;
			}else{
				$sender->sendMessage("\${".$title."}: " . TextFormat::YELLOW . $value);
				$sender->sendMessage(TextFormat::BOLD . TextFormat::UNDERLINE . "Details");
				foreach($details as $data){
					$sender->sendMessage("- \${" . $prefix . $data["required"] . "}: " . TextFormat::AQUA . $data["value"] .
						TextFormat::WHITE . " (" . TextFormat::GRAY . $data["optional"] . TextFormat::WHITE . $data["required"] . ") " .
						TextFormat::GREEN . "Type \"detail \" to see details"); //TODO
				}
				$choice = yield $this->waitDetail();
				// TODO implement
			}
			$chosen = $details[$choice];
			yield $this->showInfo($registry, array_merge($stack, [$chosen["required"]]), $chosen["info"], $sender);
		}
	}

	private function createInfoMenu(string $title, string $value, string $prefix, array $dataArray, Closure $onSubmit, Closure $onClose) : MenuForm{
		$options = [];
		foreach($dataArray as $data){
			$options[] = new MenuOption("- \${" . $prefix . $data["required"] . "}: " . TextFormat::AQUA . $data["value"] .
				TextFormat::WHITE . "\n(" . TextFormat::GRAY . $data["optional"] . TextFormat::WHITE . $data["required"] . ")");
		}
		return new MenuForm($title, $value, $options, $onSubmit, $onClose);
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}
}
