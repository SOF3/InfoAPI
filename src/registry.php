<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

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
