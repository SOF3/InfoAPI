<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use Shared\SOFe\InfoAPI\Registry;

final class Index {
	public static function register(Registry $registry) : void {
		StringNode::register($registry);
	}
}
