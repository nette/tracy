<?php

/**
 * Test: Tracy\Dumper::toText() specials
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


// resource
$f = fopen(__FILE__, 'r');
Assert::match("stream resource @%d%\n   %S%%A%", Dumper::toText($f));

fclose($f);
Assert::match('closed resource @%d%', Dumper::toText($f));


// closure
Assert::match(
	<<<'XX'
		Closure() #%d%
		XX,
	Dumper::toText(function () {}),
);


Assert::match(
	<<<'XX'
		Closure($x, $y) #%d%
		   file: '%a%:%d%'
		   use: $use
		   |  $use: null
		XX,
	Dumper::toText(function ($x, int $y = 1) use (&$use) {}, [Dumper::LOCATION => Dumper::LOCATION_CLASS]),
);


// new class
Assert::match('class@anonymous #%d%', Dumper::toText(new class {
}));


// SplFileInfo
Assert::match("SplFileInfo #%d%
   path: '%a%'
", Dumper::toText(new SplFileInfo(__FILE__)));


// SplObjectStorage
$objStorage = new SplObjectStorage;
$objStorage->attach($o1 = new stdClass);
$objStorage[$o1] = 'o1';
$objStorage->attach($o2 = (object) ['foo' => 'bar']);
$objStorage[$o2] = 'o2';

$objStorage->next();
$key = $objStorage->key();

Assert::match(
	<<<'XX'
		SplObjectStorage (2) #%d%
		   :
		   |  key: stdClass #%d%
		   |  value: 'o1'
		   :
		   |  key: stdClass #%d%
		   |  |  foo: 'bar'
		   |  value: 'o2'
		XX,
	Dumper::toText($objStorage),
);

Assert::same($key, $objStorage->key());


// ArrayObject
$obj = new ArrayObject(['a' => 1, 'b' => 2]);
Assert::match(
	<<<'XX'
		ArrayObject (2) #%d%
		   storage: array (2)
		   |  'a' => 1
		   |  'b' => 2
		XX,
	Dumper::toText($obj),
);

class ArrayObjectChild extends ArrayObject
{
	public $prop = 123;
}

$obj = new ArrayObjectChild(['a' => 1, 'b' => 2]);
Assert::match(<<<'XX'
	ArrayObjectChild (2) #%d%
	   prop: 123
	   storage: array (2)
	   |  'a' => 1
	   |  'b' => 2
	XX
	, Dumper::toText($obj));


// ArrayIterator
$obj = new ArrayIterator(['a', 'b']);
Assert::match(
	<<<'XX'
		ArrayIterator #%d%
		   0: 'a'
		   1: 'b'
		XX,
	Dumper::toText($obj),
);


// DateTime
$obj = new DateTime('1978-01-23');
Assert::match(
	<<<'XX'
		DateTime #%d%
		   date: '1978-01-23 00:00:00.000000'
		   timezone_type: %d%
		   timezone: '%a%'
		XX,
	Dumper::toText($obj),
);


// Tracy\Dumper\Value
$obj = new Tracy\Dumper\Value(Tracy\Dumper\Value::TypeText, 'ahoj');
Assert::match(
	<<<'XX'
		Tracy\Dumper\Value #%d%
		   type: 'text'
		   value: 'ahoj'
		   length: null
		   depth: null
		   id: null
		   holder: unset
		   items: null
		   editor: null
		   collapsed: null
		XX,
	Dumper::toText($obj),
);
