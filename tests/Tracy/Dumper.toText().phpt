<?php

/**
 * Test: Tracy\Dumper::toText()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


Assert::match('null', Dumper::toText(null));

Assert::match('true', Dumper::toText(true));

Assert::match('false', Dumper::toText(false));

Assert::match('0', Dumper::toText(0));

Assert::match('1', Dumper::toText(1));

Assert::match('0.0', Dumper::toText(0.0));

Assert::match('0.1', Dumper::toText(0.1));

Assert::match('""', Dumper::toText(''));

Assert::match('"0"', Dumper::toText('0'));

Assert::match('"\\x00"', Dumper::toText("\x00"));

Assert::match('array (5)
   0 => 1
   1 => "hello" (5)
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
', Dumper::toText([1, 'hello', [], [1, 2], [1 => 1, 2, 3, 4, 5, 6, 7]]));

Assert::match("stream resource #%d%\n   %S%%A%", Dumper::toText(fopen(__FILE__, 'r')));

Assert::match('stdClass #%a%', Dumper::toText(new stdClass));

Assert::match('stdClass #%a%
   "" => "foo" (3)
', Dumper::toText((object) ['' => 'foo']));

Assert::match('Test #%a%
   x => array (2)
   |  0 => 10
   |  1 => null
   y private => "hello" (5)
   z protected => 30.0
', Dumper::toText(new Test));


$objStorage = new SplObjectStorage();
$objStorage->attach($o1 = new stdClass);
$objStorage[$o1] = 'o1';
$objStorage->attach($o2 = (object) ['foo' => 'bar']);
$objStorage[$o2] = 'o2';

$objStorage->next();
$key = $objStorage->key();

Assert::match('SplObjectStorage #%a%
   0 => array (2)
   |  object => stdClass #%a%
   |  data => "o1" (2)
   1 => array (2)
   |  object => stdClass #%a%
   |  |  foo => "bar" (3)
   |  data => "o2" (2)
', Dumper::toText($objStorage));

Assert::same($key, $objStorage->key());
