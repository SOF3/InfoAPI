<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use Closure;
use pocketmine\event\Event;
use pocketmine\plugin\Plugin;
use SOFe\AwaitGenerator\GeneratorUtil;
use SOFe\AwaitGenerator\Traverser;
use SOFe\PmEvent\Events;

interface InitContext {
	/**
	 * @template E of Event
	 * @param class-string<E>[] $events
	 * @param Closure(E): string $interpreter
	 * @return Traverser<E>
	 */
	public function watchEvent(array $events, string $key, Closure $interpreter) : Traverser;
}

final class PluginInitContext implements InitContext {
	public function __construct(private Plugin $plugin) {
	}

	public function watchEvent(array $events, string $key, Closure $interpreter) : Traverser {
		return Events::watch($this->plugin, $events, $key, $interpreter);
	}
}

final class MockInitContext implements InitContext {
	public function watchEvent(array $events, string $key, Closure $interpreter) : Traverser {
		return new Traverser(GeneratorUtil::empty());
	}
}
