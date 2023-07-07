<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use Closure;
use Shared\SOFe\InfoAPI\Mapping;
use Shared\SOFe\InfoAPI\Node;
use Shared\SOFe\InfoAPI\Parameter\Parameter;
use function explode;

final class BaseMapping implements Mapping {
	/** @var string[] $fqn */
	private array $fqn;

	/**
	 * @param Closure(Node, mixed[]=): ?Node $mapper
	 * @param Parameter[] $parameters
	 */
	public function __construct(
		private string $sourceKind,
		private string $targetKind,
		string $fqn,
		private Closure $mapper,
		private bool $canImplicit = false,
		private array $parameters = [],
	) {
		$this->fqn = explode(self::FQN_SEPARATOR, $fqn);
	}

	public function getFullyQualifiedName() : array {
		return $this->fqn;
	}
	public function getSourceKind() : string {
		return $this->sourceKind;
	}
	public function getTargetKind() : string {
		return $this->targetKind;
	}

	public function canImplicit() : bool {
		return $this->canImplicit;
	}

	public function getParameters() : array {
		return $this->parameters;
	}

	public function map(Node $input, array $args) : ?Node {
		return ($this->mapper)($input, $args);
	}
}
