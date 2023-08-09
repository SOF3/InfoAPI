<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\KindMeta;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\ReflectHint;
use Shared\SOFe\InfoAPI\Registry;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\InitContext;
use SOFe\InfoAPI\KindMetadataKeys;
use SOFe\InfoAPI\ReflectUtil;

final class Index {
	public const STANDARD_KINDS = [
		"string" => Standard\StringInfo::KIND,
		"int" => Standard\IntInfo::KIND,
		"float" => Standard\FloatInfo::KIND,
		"bool" => Standard\BoolInfo::KIND,
		Server::class => Standard\BaseContext::KIND,
		Player::class => Standard\PlayerInfo::KIND,
		Position::class => Standard\PositionInfo::KIND,
		Vector3::class => Standard\VectorInfo::KIND,
		World::class => Standard\WorldInfo::KIND,
		Block::class => Standard\BlockTypeInfo::KIND,
	];

	public static function register(InitContext $initCtx, Indices $indices) : void {
		Strings::register($indices);
		Ints::register($indices);
		Floats::register($indices);
		Bools::register($indices);
		Formats::register($indices);

		Vectors::register($indices);
		Positions::register($indices);
		Players::register($initCtx, $indices);
		Worlds::register($initCtx, $indices);
		Blocks::register($indices);

		$indices->registries->kindMetas->register(new KindMeta(Standard\BaseContext::KIND, "Global functions", "You can use mappings from here", [
			KindMetadataKeys::IS_ROOT => true,
			KindMetadataKeys::BROWSER_TEMPLATE_NAME => "(None)",
		]));
	}

	/**
	 * We register all standard kinds due ot cyclic dependency between standard mappings.
	 *
	 * This should not happen in plugins because they should have a clear dependency relationship.
	 *
	 * @param Registry<ReflectHint> $defaults
	 */
	public static function registerStandardKinds(Registry $defaults) : void {
		foreach (self::STANDARD_KINDS as $class => $kind) {
			/** @var class-string $class */ // HACK: $class may be primitive type names instead, but no need to fix it
			ReflectUtil::knowKind(hints: $defaults, class: $class, kind: $kind);
		}
	}

	/** @var ?array{Registry<Display>, Registry<Mapping>} */
	public static ?array $reused = null;
}
