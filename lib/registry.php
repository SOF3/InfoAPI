<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use Shared\SOFe\InfoAPI\Display;
use Shared\SOFe\InfoAPI\KindMeta;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\ReflectHint;
use Shared\SOFe\InfoAPI\Registry;
use function array_splice;
use function count;

/**
 * A Registry implementation to fill the GlobalRegistrySingleton.
 *
 * This implementation should be as simple as possible to minimize possible bugs.
 * For the sake of simplicity, indexing should be deferred to a separate wrapper object.
 *
 * @template T
 * @implements Registry<T>
 */
final class RegistryImpl implements Registry {
	/** @var T[] $objects */
	private array $objects = [];

	private int $generation = 0;

	/**
	 * @param ?Registry<T> $field
	 * @return Registry<T>
	 */
	public static function getInstance(?Registry &$field) : Registry {
		return $field ??= new self;
	}

	public function register($object) : void {
		$this->objects[] = $object;
		$this->generation += 1;
	}

	public function getGeneration() : int {
		return $this->generation;
	}

	public function getAll() : array {
		return $this->objects;
	}
}

/**
 * Maintains search indices for objects from multiple registries.
 *
 * @template T
 */
abstract class Index {
	/** @var ?list<int> */
	private ?array $lastSyncGenerations = null;

	/**
	 * @param Registry<T>[] $registries
	 */
	public function __construct(
		private array $registries,
	) {
	}

	/**
	 * @param Registry<T> $newRegistry
	 */
	public function addLocalRegistry(int $position, Registry $newRegistry) : void {
		array_splice($this->registries, $position, 0, [$newRegistry]);
	}

	private function isSynced() : bool {
		if ($this->lastSyncGenerations === null || count($this->lastSyncGenerations) !== count($this->registries)) {
			return false;
		}

		foreach ($this->registries as $i => $registry) {
			if ($registry->getGeneration() !== $this->lastSyncGenerations[$i]) {
				return false;
			}
		}

		return true;
	}

	public function sync() : void {
		if ($this->isSynced()) {
			return;
		}

		$this->reset();
		$this->lastSyncGenerations = [];

		foreach ($this->registries as $i => $registry) {
			$this->lastSyncGenerations[$i] = $registry->getGeneration();

			foreach ($registry->getAll() as $object) {
				$this->index($object);
			}
		}
	}

	public abstract function reset() : void;

	/**
	 * @param T $object
	 */
	public abstract function index($object) : void;
}

final class Registries {
	/**
	 * @param Registry<KindMeta> $kindMetas
	 * @param Registry<Display> $displays
	 * @param Registry<Mapping> $mappings
	 * @param Registry<ReflectHint> $hints
	 */
	public function __construct(
		public Registry $kindMetas,
		public Registry $displays,
		public Registry $mappings,
		public Registry $hints,
	) {
	}

	public static function empty() : self {
		/** @var Registry<KindMeta> $kindMetas */
		$kindMetas = new RegistryImpl;
		/** @var Registry<Display> $displays */
		$displays = new RegistryImpl;
		/** @var Registry<Mapping> $mappings */
		$mappings = new RegistryImpl;
		/** @var Registry<ReflectHint> $hints */
		$hints = new RegistryImpl;

		return new self(
			kindMetas: $kindMetas,
			displays: $displays,
			mappings: $mappings,
			hints: $hints,
		);
	}

	public static function singletons() : self {
		/** @var Registry<KindMeta> $kindMetas */
		$kindMetas = RegistryImpl::getInstance(KindMeta::$global);
		/** @var Registry<Display> $displays */
		$displays = RegistryImpl::getInstance(Display::$global);
		/** @var Registry<Mapping> $mappings */
		$mappings = RegistryImpl::getInstance(Mapping::$global);
		/** @var Registry<ReflectHint> $hints */
		$hints = RegistryImpl::getInstance(ReflectHint::$global);

		return new self(
			kindMetas: $kindMetas,
			displays: $displays,
			mappings: $mappings,
			hints: $hints,
		);
	}
}

final class Indices implements ReadIndices {
	/**
	 * @param Registries[] $fallbackRegistries The non-default registries that this Indices object reads from.
	 */
	public function __construct(
		public Registries $registries,
		public DisplayIndex $displays,
		public NamedMappingIndex $namedMappings,
		public ImplicitMappingIndex $implicitMappings,
		public ReflectHintIndex $hints,
		public array $fallbackRegistries = [],
	) {
	}

	public static function forTest() : Indices {
		$registries = Registries::empty();
		return new self(
			registries: $registries,
			displays: new DisplayIndex([$registries->displays]),
			namedMappings: new NamedMappingIndex([$registries->mappings]),
			implicitMappings: new ImplicitMappingIndex([$registries->mappings]),
			hints: new ReflectHintIndex([$registries->hints]),
			fallbackRegistries: [],
		);
	}

	public static function withDefaults(InitContext $initCtx, Registries $extension) : Indices {
		$defaults = Registries::empty();
		Defaults\Index::registerStandardKinds($defaults->hints);

		$indices = new Indices(
			registries: $defaults,
			displays: new DisplayIndex([$defaults->displays, $extension->displays]),
			namedMappings: new NamedMappingIndex([$defaults->mappings, $extension->mappings]),
			implicitMappings: new ImplicitMappingIndex([$defaults->mappings, $extension->mappings]),
			hints: new ReflectHintIndex([$defaults->hints, $extension->hints]),
			fallbackRegistries: [$defaults],
		);
		Defaults\Index::register($initCtx, $indices);

		$indices->registries = $extension;

		return $indices;
	}

	public function getDisplays() : DisplayIndex {
		return $this->displays;
	}
	public function getNamedMappings() : NamedMappingIndex {
		return $this->namedMappings;
	}
	public function getImplicitMappings() : ImplicitMappingIndex {
		return $this->implicitMappings;
	}
	public function getReflectHints() : ReflectHintIndex {
		return $this->hints;
	}

	public function readonly() : ReadonlyIndices {
		return new ReadonlyIndices(
			displays: $this->displays,
			namedMappings: $this->namedMappings,
			implicitMappings: $this->implicitMappings,
			hints: $this->hints,
		);
	}
}

interface ReadIndices {
	public function getDisplays() : DisplayIndex ;
	public function getNamedMappings() : NamedMappingIndex ;
	public function getImplicitMappings() : ImplicitMappingIndex ;
	public function getReflectHints() : ReflectHintIndex ;
}

final class ReadonlyIndices implements ReadIndices {
	public function __construct(
		public DisplayIndex $displays,
		public NamedMappingIndex $namedMappings,
		public ImplicitMappingIndex $implicitMappings,
		public ReflectHintIndex $hints,
	) {
	}

	public function getDisplays() : DisplayIndex {
		return $this->displays;
	}
	public function getNamedMappings() : NamedMappingIndex {
		return $this->namedMappings;
	}
	public function getImplicitMappings() : ImplicitMappingIndex {
		return $this->implicitMappings;
	}
	public function getReflectHints() : ReflectHintIndex {
		return $this->hints;
	}
}
