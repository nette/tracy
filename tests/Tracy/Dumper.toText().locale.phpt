<?php

/**
 * Test: Tracy\Dumper::toText() locale
 */

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


setlocale(LC_ALL, 'czech');

Assert::match('array (2)
   0 => -10.0
   1 => 10.3
', Dumper::toText([-10.0, 10.3]));
