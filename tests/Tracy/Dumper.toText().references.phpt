<?php

/**
 * Test: Tracy\Dumper::toText() references
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


$a = 1;
$b = 2;
$obj = (object) [&$a, $a, &$b, $b, (object) [&$a, &$b], (object) [$a, $b], [&$b, &$a]];

Assert::match(
	<<<'XX'
		stdClass #%d%
		   0: &1 1
		   1: 1
		   2: &2 2
		   3: 2
		   4: stdClass #%d%
		   |  0: &1 1
		   |  1: &2 2
		   5: stdClass #%d%
		   |  0: 1
		   |  1: 2
		   6: array (2)
		   |  0 => &2 2
		   |  1 => &1 1
		XX,
	Dumper::toText($obj),
);
