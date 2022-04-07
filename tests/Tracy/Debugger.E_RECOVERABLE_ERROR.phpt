<?php

/**
 * Test: Tracy\Debugger E_RECOVERABLE_ERROR error.
 * @phpversion < 7.4
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;

Debugger::enable();


class TestClass
{
	public function test1(array $val)
	{
	}


	public function test2(self $val)
	{
	}


	public function __toString()
	{
		return false;
	}
}


$obj = new TestClass;

Assert::exception(
	fn() => $obj->test1('hello'),
	TypeError::class,
	'Argument 1 passed to TestClass::test1() must be %a% array, string given, called in %a%',
);

Assert::exception(
	fn() => $obj->test2('hello'),
	TypeError::class,
	'Argument 1 passed to TestClass::test2() must be an instance of TestClass, string given, called in %a%',
);

Assert::exception(
	fn() => (string) $obj,
	ErrorException::class,
	'Method TestClass::__toString() must return a string value',
);
