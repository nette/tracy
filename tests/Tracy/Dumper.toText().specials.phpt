<?php

/**
 * Test: Tracy\Dumper::toText() specials
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


// resource
Assert::match("stream resource #%d%\n   %S%%A%", Dumper::toText(fopen(__FILE__, 'r')));


// closure
Assert::match('Closure #%a%
   file => "%a%" (%i%)
   line => %i%
   variables => array ()
   parameters => ""
', Dumper::toText(function () {}));


// new class
Assert::match('class@anonymous #%a%', Dumper::toText(new class {
}));


// SplFileInfo
Assert::match('SplFileInfo #%a%
   path => "%a%" (%i%)
', Dumper::toText(new SplFileInfo(__FILE__)));


// SplObjectStorage
$objStorage = new SplObjectStorage;
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
