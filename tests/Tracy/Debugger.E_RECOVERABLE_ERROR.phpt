<?php

/**
 * Test: Tracy\Debugger E_RECOVERABLE_ERROR error.
 */

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

Assert::exception(function () use ($obj) {
	// Invalid argument #1
	$obj->test1('hello');
}, PHP_MAJOR_VERSION < 7 ? 'ErrorException' : 'TypeError', 'Argument 1 passed to TestClass::test1() must be %a% array, string given, called in %a%');

Assert::exception(function () use ($obj) {
	// Invalid argument #2
	$obj->test2('hello');
}, PHP_MAJOR_VERSION < 7 ? 'ErrorException' : 'TypeError', 'Argument 1 passed to TestClass::test2() must be an instance of TestClass, string given, called in %a%');

Assert::exception(function () use ($obj) {
	// Invalid toString
	echo $obj;
}, 'ErrorException', 'Method TestClass::__toString() must return a string value');
