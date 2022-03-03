<?php

/*
 * InfoAPI
 *
 * Copyright (C) 2019-2021 SOFe
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace SOFe\InfoAPI;

use function explode;
use function file_put_contents;
use function mkdir;
use function shell_exec;
use function sprintf;
use function str_replace;
use function ucfirst;

final class DocGenerator {
	static public function main() : void {
		$api = InfoAPI::createForTesting();
		Defaults::initAll($api);

		if(!is_dir("./docs")) {
			mkdir("./docs");
		}
		file_put_contents("./docs/defaults.dot", self::dotGenerate($api));
		shell_exec("sfdp -Tsvg -o./docs/defaults.svg ./docs/defaults.dot");
		shell_exec("sfdp -Tpng -o./docs/defaults.png ./docs/defaults.dot");

		file_put_contents("./docs/defaults.md", self::mdGenerate($api));
	}

	static private function dotGenerate(InfoAPI $api) : string {
		$nodes = [];
		$edges = [];

		foreach($api->getGraph()->getFromIndex() as $src => $list) {
			foreach($list->iterAllEdges() as $listedEdge) {
				$edge = $listedEdge->edge;
				$dest = $listedEdge->target;

				$srcClean = self::dotCleanFqn($src);
				$destClean = self::dotCleanFqn($dest);
				$nodes[$srcClean] = $src::getInfoType();
				$nodes[$destClean] = $dest::getInfoType();

				$key = "$srcClean:$destClean";
				if(!isset($edges[$key])) {
					$edges[$key] = [];
				}

				$name = $edge->getName();
				$edges[$key][] = $name !== null ? $name->toString() : "(fallback)";
			}
		}

		$output = "digraph InfoAPI_Defaults {\n";
		$output .= "\tgraph [pad=0.5, nodesep=0.5];\n";
		// $output .= "\tsplines = false;\n";

		foreach($nodes as $id => $label) {
			$output .= sprintf("\t%s [label = \"%s\", shape = \"box\"]\n", $id, $label);
		}

		$anonEdgeCounter = 0;
		foreach($edges as $syntax => $labels) {
			[$srcClean, $destClean] = explode(":", $syntax);
			foreach($labels as $label) {
				$ctr = $anonEdgeCounter++;
				$output .= sprintf("\t_anon_edge_%d [label = \"%s\", shape = \"diamond\"]\n", $ctr, $label);
				$output .= sprintf("\t%s -> _anon_edge_%d\n", $srcClean, $ctr);
				$output .= sprintf("\t_anon_edge_%d -> %s\n", $ctr, $destClean);
			}
		}

		$output .= "}\n";

		return $output;
	}

	static private function dotCleanFqn(string $fqn) : string {
		return str_replace("\\", "_", $fqn);
	}

	static private function mdGenerate(InfoAPI $api) : string {
		$output = "# Builtin info types\n";

		foreach($api->getGraph()->getFromIndex() as $src => $list) {
			$output .= sprintf("## %s\n", ucfirst($src::getInfoType()));

			$output .= "\n";
			$output .= "| Name | Output type | Description | Example |\n";
			$output .= "| :---: | :---: | :---: | :---: |\n";
			foreach($list->iterAllEdges() as $listedEdge) {
				$edge = $listedEdge->edge;
				$dest = $listedEdge->target;

				$name = $edge->getName();
				$label = $name !== null ? sprintf("`%s`", $name->toString()) : "(fallback)";

				$output .= sprintf("| %s | %s | %s | %s |\n", $label, ucfirst($dest::getInfoType()),
					$edge->getMetadata("description") ?? "", $edge->getMetadata("example") ?? null);
			}
			$output .= "\n";
		}

		return $output;
	}
}
