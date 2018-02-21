<?php

/**
 * Test: Tracy\Dumper::toText()
 * @phpVersion 5.6
 */

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';

Assert::match('TestDebugInfo #%a%
   a => 20
   x => array (2)
   |  0 => 10
   |  1 => null
   b => "virtual" (7)
   d private => "visible" (7)
   z protected => 30.0
', Dumper::toText(new TestDebugInfo));
