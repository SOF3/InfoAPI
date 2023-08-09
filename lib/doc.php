<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

use stdClass;

final class Doc {
	public static function export(Registries ...$registriesList) : mixed {
		$kinds = [];
		foreach ($registriesList as $registries) {
			foreach ($registries->kindMetas->getAll() as $help) {
				$kinds[$help->kind]["help"] = $help->help;
				$kinds[$help->kind]["metadata"] = $help->metadata ?: new stdClass;
			}
			foreach ($registries->displays->getAll() as $display) {
				$kinds[$display->kind]["canDisplay"] = true;
			}
		}

		$mappings = [];
		foreach ($registriesList as $registries) {
			foreach ($registries->mappings->getAll() as $mapping) {
				$params = [];
				foreach ($mapping->parameters as $param) {
					$params[] = [
						"name" => $param->name,
						"kind" => $param->kind,
						"multi" => $param->multi,
						"optional" => $param->optional,
					];
				}

				$mappings[] = [
					"sourceKind" => $mapping->sourceKind,
					"targetKind" => $mapping->targetKind,
					"name" => (new FullyQualifiedName($mapping->qualifiedName))->toString(),
					"isImplicit" => $mapping->isImplicit,
					"parameters" => $params,
					"mutable" => $mapping->subscribe !== null,
					"help" => $mapping->help,
					"metadata" => $mapping->metadata,
				];
			}
		}

		return [
			"kinds" => $kinds,
			"mappings" => $mappings,
		];
	}
}
