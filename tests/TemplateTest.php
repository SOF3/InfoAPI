<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Template;

use PHPUnit\Framework\TestCase;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use SOFe\InfoAPI\Ast;
use SOFe\InfoAPI\Indices;

final class TemplateTest extends TestCase {
	private static function setupIndices() : Indices {
		$indices = Indices::forTest();

		$indices->registries->displays->register(new Display("bar", fn($s) => "$s@bar"));
		$indices->registries->mappings->register(new Mapping(
			qualifiedName: ["root", "mod1", "dupTest"],
			sourceKind: "foo",
			targetKind: "bar",
			isImplicit: false,
			parameters: [],
			map: function($v) {
				return $v . "+mod1";
			},
			subscribe: null,
			help: "",
		));
		$indices->registries->mappings->register(new Mapping(
			qualifiedName: ["root", "mod2", "dupTest"],
			sourceKind: "foo",
			targetKind: "bar",
			isImplicit: false,
			parameters: [],
			map: function($v) {
				return $v . "+mod2";
			},
			subscribe: null,
			help: "",
		));
		$indices->registries->mappings->register(new Mapping(
			qualifiedName: ["root", "mod1", "toNull"],
			sourceKind: "foo",
			targetKind: "bar",
			isImplicit: false,
			parameters: [],
			map: function($v) {
				return null;
			},
			subscribe: null,
			help: "",
		));
		$indices->registries->mappings->register(new Mapping(
			qualifiedName: ["root", "mod1", "toQux"],
			sourceKind: "foo",
			targetKind: "qux",
			isImplicit: true,
			parameters: [],
			map: function($v) {
				return $v . "+toQux";
			},
			subscribe: null,
			help: "",
		));
		$indices->registries->mappings->register(new Mapping(
			qualifiedName: ["root", "mod1", "toCorge"],
			sourceKind: "qux",
			targetKind: "corge",
			isImplicit: false,
			parameters: [],
			map: function($v) {
				return $v . "+toCorge";
			},
			subscribe: null,
			help: "",
		));
		$indices->registries->displays->register(new Display("grault", fn($s) => "$s@grault"));
		$indices->registries->mappings->register(new Mapping(
			qualifiedName: ["root", "mod1", "toGrault"],
			sourceKind: "corge",
			targetKind: "grault",
			isImplicit: true,
			parameters: [],
			map: function($v) {
				return $v . "+toGrault";
			},
			subscribe: null,
			help: "",
		));
		$indices->registries->mappings->register(new Mapping(
			qualifiedName: ["root", "mod1", "maybeImplicit"],
			sourceKind: "qux",
			targetKind: "grault",
			isImplicit: true,
			parameters: [],
			map: function($v) {
				return $v . "+throughQux";
			},
			subscribe: null,
			help: "",
		));
		$indices->registries->mappings->register(new Mapping(
			qualifiedName: ["root", "mod1", "maybeImplicit"],
			sourceKind: "foo",
			targetKind: "bar",
			isImplicit: true,
			parameters: [],
			map: function($v) {
				return $v . "+direct";
			},
			subscribe: null,
			help: "",
		));
		$indices->registries->mappings->register(new Mapping(
			qualifiedName: ["root", "mod1", "graultGrault"],
			sourceKind: "grault",
			targetKind: "grault",
			isImplicit: true,
			parameters: [],
			map: function($v) {
				return $v . "+graultGrault";
			},
			subscribe: null,
			help: "",
		));

		return $indices;
	}

	private static function assertTemplate(string $template, string $sourceKind, mixed $value, string $expect) : void {
		$ast = Ast\Parse::parse($template);
		$template = Template::fromAst($ast, self::setupIndices(), $sourceKind);
		$result = $template->display($value, null);
		self::assertSame($expect, $result);
	}

	public function testDup() : void {
		self::assertTemplate("lorem {mod1:dupTest} ipsum", "foo", "init", "lorem init+mod1@bar ipsum");
	}

	public function testFallback() : void {
		self::assertTemplate("lorem {toNull | mod2:dupTest} ipsum", "foo", "init", "lorem init+mod2@bar ipsum");
	}

	public function testImplicit() : void {
		self::assertTemplate("lorem {toCorge} ipsum", "foo", "init", "lorem init+toQux+toCorge+toGrault@grault ipsum");
	}

	public function testPreferShort() : void {
		self::assertTemplate("lorem {maybeImplicit} ipsum", "foo", "init", "lorem init+direct@bar ipsum");
	}

	public function testLongerPath() : void {
		self::assertTemplate("lorem {maybeImplicit graultGrault} ipsum", "foo", "init", "lorem init+toQux+throughQux+graultGrault@grault ipsum");
	}
}
