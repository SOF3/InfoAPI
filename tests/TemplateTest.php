<?php

declare(strict_types=1);

namespace SOFe\InfoAPI\Template;

use PHPUnit\Framework\TestCase;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Parameter;
use SOFe\InfoAPI\Ast;
use SOFe\InfoAPI\Indices;
use function json_encode;

final class TemplateTest extends TestCase {
	private static function setupIndices() : Indices {
		$indices = Indices::forTest();

		$displayableKinds = ["bar", "grault"];
		foreach ($displayableKinds as $kind) {
			$indices->registries->displays->register(new Display($kind, function(mixed $s) use ($kind) {
				self::assertIsString($s);
				return "$s@$kind";
			}));
		}

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
			metadata: [],
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
			metadata: [],
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
			metadata: [],
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
			metadata: [],
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
			metadata: [],
		));
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
			metadata: [],
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
			metadata: [],
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
			metadata: [],
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
			metadata: [],
		));
		$indices->registries->mappings->register(new Mapping(
			qualifiedName: ["root", "mod1", "withParams"],
			sourceKind: "foo",
			targetKind: "grault",
			isImplicit: false,
			parameters: [
				new Parameter("theRequired", "qux", multi: false, optional: false, metadata: []),
				new Parameter("theOptional", "corge", multi: false, optional: true, metadata: []),
			],
			map: function($v, $args) {
				return $v . ";" . json_encode($args[0]) . ";" . json_encode($args[1]);
			},
			subscribe: null,
			help: "",
			metadata: [],
		));

		return $indices;
	}

	private static function assertTemplate(string $template, string $sourceKind, mixed $value, string $expect) : void {
		$ast = Ast\Parse::parse($template);
		$template = Template::fromAst($ast, self::setupIndices(), $sourceKind);
		$result = $template->render($value, null, new Get)->get();
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

	public function testOmittedNamedArgs() : void {
		self::assertTemplate("lorem {withParams(theRequired = toQux)} ipsum", "foo", "init", 'lorem init;"init+toQux";null@grault ipsum');
	}

	public function testOmittedUnnamedArgs() : void {
		self::assertTemplate("lorem {withParams(toQux)} ipsum", "foo", "init", 'lorem init;"init+toQux";null@grault ipsum');
	}

	public function testFullNamedArgs() : void {
		self::assertTemplate("lorem {withParams(theRequired = toQux, theOptional = toCorge)} ipsum", "foo", "init", 'lorem init;"init+toQux";"init+toQux+toCorge"@grault ipsum');
	}

	public function testFullUnnamedArgs() : void {
		self::assertTemplate("lorem {withParams(toQux, toCorge)} ipsum", "foo", "init", 'lorem init;"init+toQux";"init+toQux+toCorge"@grault ipsum');
	}
}
