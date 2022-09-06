<?php

/**
 * Test: Tracy\Dumper::toText() no hashes
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


$arr = [1, 2, 3];
$arr[] = &$arr;
Assert::match(<<<'XX'
array (4)
   0 => 1
   1 => 2
   2 => 3
   3 => array (4)
   |  0 => 1
   |  1 => 2
   |  2 => 3
   |  3 => array (4) RECURSION
XX
	, Dumper::toText($arr, [Dumper::HASH => false]));


$arr = (object) ['x' => 1, 'y' => 2];
$arr->z = &$arr;
Assert::match(<<<'XX'
stdClass
   x: 1
   y: 2
   z: stdClass RECURSION
XX
	, Dumper::toText($arr, [Dumper::HASH => false]));


$obj = (object) ['a' => 1];
Assert::match(<<<'XX'
array (3)
   0 => stdClass
   |  a: 1
   1 => stdClass
   |  a: 1
   2 => stdClass
   |  a: 1
XX
	, Dumper::toText([$obj, $obj, $obj], [Dumper::HASH => false]));
