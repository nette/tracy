<?php

/**
 * Test: Tracy\Dumper::toText() locale
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


setlocale(LC_ALL, 'czech');

Assert::match(
	<<<'XX'
		array (2)
		   0 => -10.0
		   1 => 10.3
		XX,
	Dumper::toText([-10.0, 10.3]),
);
