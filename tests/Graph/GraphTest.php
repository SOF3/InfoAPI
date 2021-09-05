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

namespace SOFe\InfoAPI\Graph;

use PHPUnit\Framework\TestCase;
use SOFe\InfoAPI\Dummy;
use SOFe\InfoAPI\Graph\Edge;
use SOFe\InfoAPI\Ast\ChildName;

final class GraphTest extends TestCase {
	public function testFindSimple() : void {
		$graph = self::initTestGraph();

		$path = $graph->pathFind(Dummy\A::class, [
			ChildName::parse("y"),
			ChildName::parse("z"),
		]);

		self::assertNotNull($path);

		$info = new Dummy\A("a");
		foreach($path->getResolvers() as $resolver) {
			$info = $resolver($info);
		}
		self::assertSame("abe", $info->toString());
	}

	public function testFindQualified() : void {
		$graph = self::initTestGraph();

		$path = $graph->pathFind(Dummy\A::class, [
			ChildName::parse("x.y"),
			ChildName::parse("z"),
		]);

		self::assertNotNull($path);

		$info = new Dummy\A("a");
		foreach($path->getResolvers() as $resolver) {
			$info = $resolver($info);
		}
		self::assertSame("acde", $info->toString());
	}

	public function testFindFail() : void {
		$graph = self::initTestGraph();

		$path = $graph->pathFind(Dummy\A::class, [
			ChildName::parse("x.y"),
			ChildName::parse("x"),
		]);

		self::assertNull($path);
	}

	static private function initTestGraph() : Graph {
		$graph = new Graph;
		$graph->insert(Dummy\A::class, Dummy\B::class,
			Edge::parentChild(ChildName::parse("y"), fn($a) => new Dummy\B($a->toString() . "b")));
		$graph->insert(Dummy\A::class, Dummy\C::class,
			Edge::fallback(fn($a) => new Dummy\C($a->toString() . "c")));
		$graph->insert(Dummy\C::class, Dummy\D::class,
			Edge::parentChild(ChildName::parse("x.y"), fn($c) => new Dummy\D($c->toString() . "d")));
		$graph->insert(Dummy\B::class, Dummy\E::class,
			Edge::parentChild(ChildName::parse("z"), fn($b) => new Dummy\E($b->toString() . "e")));
		$graph->insert(Dummy\D::class, Dummy\E::class,
			Edge::parentChild(ChildName::parse("z"), fn($d) => new Dummy\E($d->toString() . "e")));

		return $graph;
	}
}
