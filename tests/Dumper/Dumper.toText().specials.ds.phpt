<?php

/**
 * Test: Tracy\Dumper::toText() specials
 * @phpExtension ds
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


$collection = new Ds\Vector(['value']);
Assert::match(
	<<<'XX'
		Ds\Vector #%d%
		   0: 'value'
		XX,
	Dumper::toText($collection),
);


$map = new Ds\Map;
$map->put('key', 'value');
Assert::match(
	<<<'XX'
		Ds\Map #%d%
		   0: Ds\Pair #%d%
		   |  key: 'key'
		   |  value: 'value'
		XX,
	Dumper::toText($map),
);


$queue = new Ds\Queue(['value']);
Assert::match(
	<<<'XX'
		Ds\Queue #%d%
		   0: 'value'
		XX,
	Dumper::toText($queue),
);
Assert::count(1, $queue);


$stack = new Ds\Stack(['value']);
Assert::match(
	<<<'XX'
		Ds\Stack #%d%
		   0: 'value'
		XX,
	Dumper::toText($stack),
);
Assert::count(1, $stack);
