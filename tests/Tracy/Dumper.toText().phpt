<?php

/**
 * Test: Tracy\Dumper::toText()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


// scalars & empty array
Assert::same('null' . "\n", Dumper::toText(null));

Assert::same('true' . "\n", Dumper::toText(true));

Assert::same('false' . "\n", Dumper::toText(false));

Assert::same('0' . "\n", Dumper::toText(0));

Assert::same('1' . "\n", Dumper::toText(1));

Assert::same('0.0' . "\n", Dumper::toText(0.0));

Assert::same('0.1' . "\n", Dumper::toText(0.1));

Assert::same('INF' . "\n", Dumper::toText(INF));

Assert::same('-INF' . "\n", Dumper::toText(-INF));

Assert::same('NAN' . "\n", Dumper::toText(NAN));

Assert::same("''\n", Dumper::toText(''));

Assert::same("'0'\n", Dumper::toText('0'));

Assert::same("'\\x00'\n", Dumper::toText("\x00"));

Assert::same('array ()' . "\n", Dumper::toText([]));


// array
Assert::same(str_replace("\r", '', <<<'XX'
array (1)
   0 => 1

XX
), Dumper::toText([1]));

Assert::match(<<<'XX'
array (5)
   0 => 1
   1 => 'hello' (5)
   2 => array ()
   3 => array (2)
   |  0 => 1
   |  1 => 2
   4 => array (7)
   |  1 => 1
   |  2 => 2
   |  3 => 3
   |  4 => 4
   |  5 => 5
   |  6 => 6
   |  7 => 7
XX
, Dumper::toText([1, 'hello', [], [1, 2], [1 => 1, 2, 3, 4, 5, 6, 7]]));


// object
Assert::match('stdClass #%d%', Dumper::toText(new stdClass));

Assert::match(<<<'XX'
stdClass #%d%
   '': 'foo' (3)
XX
, Dumper::toText((object) ['' => 'foo']));

Assert::match(<<<'XX'
Test #%d%
   x: array (2)
   |  0 => 10
   |  1 => null
   y: 'hello' (5)
   z: 30.0
XX
, Dumper::toText(new Test));


$obj = new Child;
$obj->new = 7;
$obj->{0} = 8;
$obj->{1} = 9;
$obj->{''} = 10;

Assert::match(<<<'XX'
Child #%d%
   x: 1
   y: 2
   z: 3
   x2: 4
   y2: 5
   z2: 6
   y: 'hello' (5)
   new: 7
   0: 8
   1: 9
   '': 10
XX
, Dumper::toText($obj));
