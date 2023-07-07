<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use pocketmine\command\CommandSender;
use Shared\SOFe\InfoAPI\Info;
use Shared\SOFe\InfoAPI\Node;
use Shared\SOFe\InfoAPI\Registry;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\BaseMapping;
use function mb_strtolower;
use function mb_strtoupper;

/**
 * Implements a standard string node.
 */
final class StringNode implements Node, Info, Standard\StringNode {
	public function __construct(public string $value) {
	}

	public function get() : string {
		return $this->value;
	}

	public function getKind() : string {
		return self::KIND;
	}

	public function display(CommandSender $target) : string {
		return $this->value;
	}

	public static function register(Registry $registry) : void {
		$registry->provideMapping(new BaseMapping(
			sourceKind: Standard\StringNode::KIND,
			targetKind: Standard\StringNode::KIND,
			fqn: "infoapi:uppercase",
			mapper: fn(Node $node) => $node instanceof Standard\StringNode ? new self(mb_strtolower($node->get())) : null,
		));
		$registry->provideMapping(new BaseMapping(
			sourceKind: Standard\StringNode::KIND,
			targetKind: Standard\StringNode::KIND,
			fqn: "infoapi:lowercase",
			mapper: fn(Node $node) => $node instanceof Standard\StringNode ? new self(mb_strtoupper($node->get())) : null,
		));
	}
}
