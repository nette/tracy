<?php

/**
 * Test: Tracy\Dumper::toText() recursion
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


$arr = [1, 2, 3];
$arr[] = &$arr;
Assert::match('array (4)
   0 => 1
   1 => 2
   2 => 3
   3 => array (3) [ RECURSION ]
', Dumper::toText($arr));


$arr = (object) ['x' => 1, 'y' => 2];
$arr->z = &$arr;
Assert::match('stdClass #%a%
   x => 1
   y => 2
   z => stdClass #%a% { RECURSION }
', Dumper::toText($arr));
