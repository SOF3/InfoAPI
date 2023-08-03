<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Defaults;

use PHPUnit\Framework\TestCase;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Standard;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\MockInitContext;
use SOFe\InfoAPI\QualifiedRef;
use SOFe\InfoAPI\Registries;

final class StringsTest extends TestCase {
	/**
	 * @param string[] $name
	 */
	private static function mapping(array $name) : Mapping {
		$indices = Indices::withDefaults(new MockInitContext, Registries::empty());
		$mappings = $indices->namedMappings->find(Standard\StringInfo::KIND, new QualifiedRef($name));
		self::assertCount(1, $mappings);
		return $mappings[0]->mapping;
	}

	/**
	 * @param string[] $name
	 * @param mixed[] $params
	 */
	private static function map(array $name, string $input, array $params, mixed $expect) : void {
		$mapping = self::mapping($name);
		$actual = ($mapping->map)($input, $params);
		self::assertSame($expect, $actual);
	}

	public function testUpper() : void {
		self::map(["upper"], "LorEM ipSUm", [], "LOREM IPSUM");
	}
}
