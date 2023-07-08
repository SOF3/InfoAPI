<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use pocketmine\utils\SingletonTrait;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Registry;

use function array_unshift;

/**
 * @extends Index<Mapping>
 */
final class NamedMappingIndex extends Index {
	use SingletonTrait;

	private static function make() : self {
		/** @var Registry<Mapping> $global */
		$global = RegistryImpl::getInstance(Mapping::$global);
		return new self([Defaults\Index::reusedMappings(), $global]);
	}

	/** @var array<string, array<string, Mapping>> */
	private array $namedMappings;

	public function reset() : void {
		$this->namedMappings = [];
	}

	public function index($mapping) : void {
		$source = $mapping->sourceKind;

		$name = (new FullyQualifiedName($mapping->qualifiedName))->toString();
		if (!isset($this->namedMappings[$source])) {
			$this->namedMappings[$source] = [];
		}
		$this->namedMappings[$source][$name] = $mapping;
	}

	/**
	 * @return array<string, Mapping>
	 */
	public function getNamed(string $sourceKind) : array {
		$this->sync();
		return $this->namedMappings[$sourceKind] ?? [];
	}
}

/**
 * @extends Index<Mapping>
 */
final class ImplicitMappingIndex extends Index {
	use SingletonTrait;

	private static function make() : self {
		/** @var Registry<Mapping> $global */
		$global = RegistryImpl::getInstance(Mapping::$global);
		return new self([Defaults\Index::reusedMappings(), $global]);
	}

	/** @var array<string, list<Mapping>> */
	private array $implicitMappings;

	public function reset() : void {
		$this->implicitMappings = [];
	}

	public function index($mapping) : void {
		$source = $mapping->sourceKind;

		if ($mapping->isImplicit) {
			if (!isset($this->implicitMappings[$source])) {
				$this->implicitMappings[$source] = [];
			}
			array_unshift($this->implicitMappings[$source], $mapping);
		}
	}

	/**
	 * @return list<Mapping>
	 */
	public function getImplicit(string $sourceKind) : array {
		$this->sync();
		return $this->implicitMappings[$sourceKind] ?? [];
	}
}
