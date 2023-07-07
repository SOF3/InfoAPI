<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use Shared\SOFe\InfoAPI\GlobalRegistrySingleton;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Registry;
use function array_splice;
use function array_unshift;
use function count;

/**
 * A Registry implementation to fill the GlobalRegistrySingleton.
 *
 * This implementation should be as simple as possible to minimize possible bugs.
 * For the sake of simplicity, indexing should be deferred to a separate wrapper object.
 */
final class GlobalRegistry implements Registry {
	/** @var list<Mapping> $mappings */
	private array $mappings = [];

	private int $generation = 0;

	public static function getInstance() : Registry {
		return GlobalRegistrySingleton::$global ??= new self;
	}

	public function provideMapping(Mapping $mapping) : void {
		$this->mappings[] = $mapping;
		$this->generation += 1;
	}

	public function getGeneration() : int {
		return $this->generation;
	}

	public function getAllMappings() : array {
		return $this->mappings;
	}
}

/**
 * Maintains a search index for mappings from multiple registries.
 */
final class MappingIndex {
	private static ?self $instance = null;

	/**
	 * Returns the MappingIndex singleton for this shaded version of InfoAPI.
	 *
	 * The default initialization includes a local registry with default mappings
	 * and the global registry, the latter overriding the former in case of duplicates.
	 * Users can add local registries through `addLocalRegistry`,
	 * which only affects this MappingIndex instance,
	 * hence only affecting this shaded version of InfoAPI.
	 */
	public static function get() : self {
		if (self::$instance === null) {
			$defaultRegistry = new GlobalRegistry;
			Defaults\Index::register($defaultRegistry);
			self::$instance = new self([$defaultRegistry, GlobalRegistry::getInstance()]);
		}

		return self::$instance;
	}

	/** @var ?list<int> */
	private ?array $lastSyncGenerations = null;

	/** @var array<string, array<string, Mapping>> */
	private array $namedMappings;

	/** @var array<string, list<Mapping>> */
	private array $implicitMappings;

	/**
	 * @param Registry[] $registries
	 */
	public function __construct(
		private array $registries,
	) {
	}

	public function addLocalRegistry(int $position, Registry ...$newRegistries) : void {
		array_splice($this->registries, $position, 0, [$newRegistries]);
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

	private function sync() : void {
		if ($this->isSynced()) {
			return;
		}

		$this->lastSyncGenerations = [];
		$this->namedMappings = [];
		$this->implicitMappings = [];

		foreach ($this->registries as $i => $registry) {
			$this->lastSyncGenerations[$i] = $registry->getGeneration();

			foreach ($registry->getAllMappings()as $mapping) {
				$source = $mapping->getSourceKind();

				$name = (new FullyQualifiedName($mapping->getFullyQualifiedName()))->toString();
				if (!isset($this->namedMappings[$source])) {
					$this->namedMappings[$source] = [];
				}
				$this->namedMappings[$source][$name] = $mapping;

				if ($mapping->canImplicit()) {
					if (!isset($this->implicitMappings[$source])) {
						$this->implicitMappings[$source] = [];
					}
					array_unshift($this->implicitMappings[$source], $mapping);
				}
			}
		}
	}

	/**
	 * @return array<string, Mapping>
	 */
	public function getNamed(string $sourceKind) : array {
		$this->sync();
		return $this->namedMappings[$sourceKind] ?? [];
	}

	/**
	 * @return list<Mapping>
	 */
	public function getImplicit(string $sourceKind) : array {
		$this->sync();
		return $this->implicitMappings[$sourceKind] ?? [];
	}
}
