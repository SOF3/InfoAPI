<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use Closure;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\utils\SingletonTrait;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;
use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Parameter;
use Shared\SOFe\InfoAPI\ReflectHint;
use Shared\SOFe\InfoAPI\Registry;
use function array_shift;
use function count;
use function explode;
use function get_class;
use function gettype;
use function is_object;

final class ReflectUtil {
	/**
	 * @param Registry<Display> $displays
	 * @param Registry<ReflectHint> $hints
	 */
	public static function addClosureDisplay(Registry $displays, Registry $hints, string $kind, Closure $closure) : void {
		$reflect = new ReflectionFunction($closure);
		$params = $reflect->getParameters();
		if (count($params) < 1) {
			throw new RuntimeException("Closure must have at least one parameter");
		}

		$param = $params[0];
		$type = $param->getType();
		if (!($type instanceof ReflectionNamedType) || $type->isBuiltin()) {
			throw new RuntimeException("First parameter of Closure must have a type hint that is a class defined by the plugin");
		}
		/** @var class-string $class */ // because $type->isBuiltin() is false
		$class = $type->getName();

		$hints->register(new ReflectHint(class: $class, kind: $kind));

		$display = new Display(
			kind: $kind,
			display: function(mixed $value, CommandSender $sender) use ($closure, $type) : string {
				if (!self::correctType($value, $type)) {
					return Display::INVALID;
				}
				return ($closure)($value, $sender);
			},
		);
		$displays->register($display);
	}

	/**
	 * Registers a mapping by detecting the kind from the closure.
	 *
	 * The closure must have at least one parameter.
	 * All parameters must have a type hint,
	 * and the return type hint must be present.
	 * Each type hint must map to a known kind through `ReflectUtil::knowKind()`.
	 * The second parameter onwards may be nullable.
	 *
	 * @param Registry<Mapping> $mappings
	 * @param string[] $names
	 */
	public static function addClosureMapping(
		Registry $mappings,
		ReflectHintIndex $hints,
		string $namespace,
		array $names,
		Closure $closure,
		?Closure $watchChanges = null,
		bool $isImplicit = false,
	) : void {
		$reflect = new ReflectionFunction($closure);
		$closureParams = $reflect->getParameters();

		if (count($closureParams) < 1) {
			throw new RuntimeException("Closure must have at least one parameter");
		}

		$sourceParam = array_shift($closureParams);
		$sourceRawType = $sourceParam->getType() ?? throw new RuntimeException("First parameter of Closure is not typed");
		$sourceTypes = $sourceRawType instanceof ReflectionUnionType ? $sourceRawType->getTypes() : [$sourceRawType];

		$params = [];
		$paramTypes = [];
		foreach ($closureParams as $closureParam) {
			$paramType = $closureParam->getType() ?? throw new RuntimeException("Parameter of Closure is not typed");
			if ($paramType instanceof ReflectionUnionType) {
				throw new RuntimeException("Only the first parameter can be union type");
			}
			/** @var ReflectionNamedType $paramType */

			$param = new Parameter(
				name: $closureParam->getName(),
				kind: $hints->lookup($paramType->getName()) ?? throw new RuntimeException("Cannot detect info kind for parameter type {$paramType->getName()}"),
				multi: $closureParam->isVariadic(),
				optional: $paramType->allowsNull(),
			);

			$params[] = $param;
			$paramTypes[] = $paramType;
		}

		$returnType = $reflect->getReturnType() ?? throw new RuntimeException("Closure must have explicit return type hint");
		if ($returnType instanceof ReflectionUnionType) {
			throw new RuntimeException("Return type must not be union");
		}

		/** @var ReflectionNamedType $returnType */
		$targetKind = $hints->lookup($returnType->getName()) ?? throw new RuntimeException("Cannot detect info kind for return type {$returnType->getName()}");

		$nsTokens = explode(Mapping::FQN_SEPARATOR, $namespace);

		foreach ($sourceTypes as $sourceType) {
			$sourceKind = $hints->lookup($sourceType->getName()) ?? throw new RuntimeException("Cannot detect info kind for source type {$sourceType->getName()}");

			/** @var ?Closure(mixed, mixed[]): Generator<mixed, mixed, mixed, void> */
			$subscribe = null;
			if ($watchChanges !== null) {
				$corrected = self::correctClosure($watchChanges, $sourceType, $paramTypes);
				$subscribe = function($source, $args) use ($corrected) : Generator {
					$gen = $corrected($source, $args);
					if ($gen === null) {
						return;
					}
					yield from $gen;
				};
			}

			foreach ($names as $name) {
				$fqnTokens = $nsTokens;
				$fqnTokens[] = $name;

				$mappings->register(new Mapping(
					qualifiedName: $fqnTokens,
					sourceKind: $sourceKind,
					targetKind: $targetKind,
					isImplicit: $isImplicit,
					parameters: $params,
					map: self::correctClosure($closure, $sourceType, $paramTypes),
					subscribe: $subscribe,
				));
			}
		}
	}

	/**
	 * @template T
	 *
	 * @param Closure(): T $impl
	 * @param ReflectionNamedType[] $paramTypes
	 * @return Closure(mixed, mixed[]): ?T
	 */
	private static function correctClosure(Closure $impl, ReflectionNamedType $sourceType, array $paramTypes) : Closure {
		return function(mixed $source, array $args) use ($impl, $sourceType, $paramTypes) {
			$outArgs = [];

			if (!self::correctType($source, $sourceType)) {
				return null;
			}
			$outArgs[] = $source;

			foreach ($args as $i => $arg) {
				if (!self::correctType($arg, $paramTypes[$i])) {
					return null;
				}
				$outArgs[] = $arg;
			}

			return $impl(...$outArgs);
		};
	}

	private static function correctType(mixed &$value, ReflectionNamedType $type) : bool {
		if (self::isAssignable($value, $type)) {
			return true;
		}
		if ($type->allowsNull()) {
			$value = null;
			return true;
		}
		return false;
	}

	private static function isAssignable(mixed $value, ReflectionNamedType $type) : bool {
		if ($type->allowsNull() && $value === null) {
			return true;
		}

		if ($type->isBuiltin()) {
			return gettype($value) === $type->getName();
		}

		if (is_object($value)) {
			return get_class($value) === $type->getName();
		}

		return false;
	}

	/**
	 * @param Registry<ReflectHint> $hints
	 * @param class-string $class
	 */
	public static function knowKind(Registry $hints, string $class, string $kind) : void {
		$hints->register(new ReflectHint(class: $class, kind: $kind));
	}
}

/**
 * @extends Index<ReflectHint>
 */
final class ReflectHintIndex extends Index {
	use SingletonTrait;

	/** @var array<class-string, string> */
	private array $map = [];

	private static function make() : self {
		/** @var Registry<ReflectHint> $defaults */
		$defaults = new RegistryImpl;
		foreach (Defaults\Index::STANDARD_KINDS as $class => $kind) {
			/** @var class-string $class */ // HACK: $class may be primitive type names instead, but no need to fix it
			ReflectUtil::knowKind(hints: $defaults, class: $class, kind: $kind);
		}

		/** @var Registry<ReflectHint> $global */
		$global = RegistryImpl::getInstance(ReflectHint::$global);

		return new self([
			$defaults,
			$global,
		]);
	}

	public function reset() : void {
		$this->map = [];
	}

	public function index($object) : void {
		$this->map[$object->class] = $object->kind;
	}

	public function lookup(string $class) : ?string {
		$this->sync();

		return $this->map[$class] ?? null;
	}
}
