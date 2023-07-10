<?php

declare(strict_types=1);

namespace SOFe\InfoAPI;

final class Doc {
	public static function export(Registries $registries) : mixed {
		$kinds = [];
		foreach ($registries->kindHelps->getAll() as $help) {
			$kinds[$help->kind]["help"] = $help->help;
		}
		foreach ($registries->displays->getAll() as $display) {
			$kinds[$display->kind]["canDisplay"] = true;
		}

		$mappings = [];
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
			];
		}

		return [
			"kinds" => $kinds,
			"mappings" => $mappings,
		];
	}
}
