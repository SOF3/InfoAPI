<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use PHPUnit\Framework\TestCase;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\ReflectHint;
use Shared\SOFe\InfoAPI\Registry;
use SOFe\InfoAPI\ReflectHintIndex;
use SOFe\InfoAPI\RegistryImpl;

final class DefaultsTest extends TestCase {
	public static function setupRegistries() : void {
		/** @var Registry<Display> $displays */
		$displays = new RegistryImpl;
		/** @var Registry<Mapping> $mappings */
		$mappings = new RegistryImpl;
		/** @var Registry<ReflectHint> $hints */
		$hints = new RegistryImpl;
		$hintsIndex = new ReflectHintIndex([$hints]);

		Index::registerStandardKinds($hints);
		Index::register($displays, $mappings, $hintsIndex);
	}
}
