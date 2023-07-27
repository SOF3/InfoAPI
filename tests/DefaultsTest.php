<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use PHPUnit\Framework\TestCase;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\MockInitContext;
use SOFe\InfoAPI\Registries;

final class DefaultsTest extends TestCase {
	public static function setupRegistries() : void {
		Indices::withDefaults(new MockInitContext, Registries::empty());
	}
}
