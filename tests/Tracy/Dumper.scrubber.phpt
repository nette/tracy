<?php

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


$obj = (object) [
	'a' => 456,
	'password' => 'secret1',
	'PASSWORD' => 'secret2',
	'Pin' => 'secret3',
	'foo' => 'bar',
	'q' => 42,
	'inner' => [
		'a' => 123,
		'password' => 'secret4',
		'PASSWORD' => 'secret5',
		'Pin' => 'secret6',
		'bar' => 42,
	],
];
$scrubber = function (string $k, $v = null): bool {
	return strtolower($k) === 'pin' || strtolower($k) === 'foo' || $v === 42;
};

$expect1 = <<<'XX'
stdClass #%d%
   a: 456
   password: 'secret1'
   PASSWORD: 'secret2'
   Pin: ***** (string)
   foo: ***** (string)
   q: ***** (integer)
   inner: array (5)
   |  'a' => 123
   |  'password' => 'secret4'
   |  'PASSWORD' => 'secret5'
   |  'Pin' => ***** (string)
   |  'bar' => ***** (integer)
XX;

Assert::match($expect1, Dumper::toText($obj, [Dumper::SCRUBBER => $scrubber]));

// scrubber works with "keys to hide" (back compatibility)
$expect2 = <<<'XX'
stdClass #%d%
   a: 456
   password: ***** (string)
   PASSWORD: ***** (string)
   Pin: ***** (string)
   foo: ***** (string)
   q: ***** (integer)
   inner: array (5)
   |  'a' => 123
   |  'password' => ***** (string)
   |  'PASSWORD' => ***** (string)
   |  'Pin' => ***** (string)
   |  'bar' => ***** (integer)
XX;
Assert::match($expect2, Dumper::toText($obj, [Dumper::SCRUBBER => $scrubber, Dumper::KEYS_TO_HIDE => ['password']]));


// class + property name test
class ParentClass
{
	public $foo;
	private $bar;
}

class ChildClass extends ParentClass
{
	public $foo2 = ['a' => 'b'];
	private $bar;
}

$scrubber = function (string $k, $v, $class) use (&$log): bool {
	$log[] = [$k, $class];
	return false; // accept
};
$log = [];
Dumper::toText(new ChildClass, [Dumper::SCRUBBER => $scrubber]);
Assert::same([
	['foo2', 'ChildClass'],
	['a', null],
	['bar', 'ChildClass'],
	['foo', 'ParentClass'],
	['bar', 'ParentClass'],
], $log);


$scrubber = function (string $k, $v, $class) use (&$log): bool {
	$log[] = [$k, $class];
	return true; // reject
};
$log = [];
Dumper::toText(new ChildClass, [Dumper::SCRUBBER => $scrubber]);
Assert::same([
	['foo2', 'ChildClass'],
	['bar', 'ChildClass'],
	['foo', 'ParentClass'],
	['bar', 'ParentClass'],
], $log);


// ignores special types
$arr = [
	'res' => fopen(__FILE__, 'r'),
	'closure' => function ($a, $b) use ($scrubber) {},
];

$scrubber = function (string $k, $v, $class) use (&$log): bool {
	$log[] = [$k, $class];
	return false; // accept
};
$log = [];
Dumper::toText($arr, [Dumper::SCRUBBER => $scrubber]);
Assert::same([
	['res', null],
	['closure', null],
], $log);
