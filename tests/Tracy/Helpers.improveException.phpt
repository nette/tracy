<?php

/**
 * Test: Tracy\Debugger suggestions
 * @phpversion 7
 */

use Tracy\Helpers;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class TestClass
{
	public $public;

	protected $protected;

	public static $publicStatic;

	public function publicMethod()
	{}

	public static function publicMethodStatic()
	{}

	protected function protectedMethod()
	{}

	protected static function protectedMethodS()
	{}
}

function myFunction()
{}


$obj = new TestClass;


// calling
test(function () {
	try {
		trimx();
	} catch (\Error $e) {}
	Helpers::improveException($e);
	Assert::same('Call to undefined function trimx(), did you mean trim()?', $e->getMessage());
});

test(function () {
	try {
		myFunctionx();
	} catch (\Error $e) {}
	Helpers::improveException($e);
	Assert::same('Call to undefined function myFunctionx(), did you mean myfunction()?', $e->getMessage());
});

test(function () {
	try {
		TestClass::publicMethodX();
	} catch (\Error $e) {}
	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::publicMethodX(), did you mean publicMethod()?', $e->getMessage());
});

test(function () use ($obj) {
	try {
		$obj->publicMethodX();
	} catch (\Error $e) {}
	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::publicMethodX(), did you mean publicMethod()?', $e->getMessage());
});

test(function () use ($obj) { // suggest static method
	try {
		$obj->publicMethodStaticX();
	} catch (\Error $e) {}
	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::publicMethodStaticX(), did you mean publicMethodStatic()?', $e->getMessage());
});

test(function () use ($obj) { // suggest only public method
	try {
		$obj->protectedMethodX();
	} catch (\Error $e) {}
	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::protectedMethodX()', $e->getMessage());
});


// reading
test(function () use ($obj) {
	@$val = $obj->publicX;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: TestClass::$publicX, did you mean $public?', $e->getMessage());
});

test(function () use ($obj) { // suggest only non-static property
	@$val = $obj->publicStaticX;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: TestClass::$publicStaticX', $e->getMessage());
});

test(function () use ($obj) { // suggest only public property
	@$val = $obj->protectedX;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: TestClass::$protectedX', $e->getMessage());
});

test(function () use ($obj) { // suggest only static property
	try {
		$val = TestClass::$publicStaticX;
	} catch (\Error $e) {}
	Helpers::improveException($e);
	Assert::same('Access to undeclared static property: TestClass::$publicStaticX, did you mean $publicStatic?', $e->getMessage());
});

test(function () use ($obj) { // suggest only public static property
	try {
		$val = TestClass::$protectedMethodX;
	} catch (\Error $e) {}
	Helpers::improveException($e);
	Assert::same('Access to undeclared static property: TestClass::$protectedMethodX', $e->getMessage());
});


// variables
test(function () use ($obj) {
	$abcd = 1;
	@$val = $abc;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	$e->context = get_defined_vars();
	Helpers::improveException($e);
	Assert::same('Undefined variable $abc, did you mean $abcd?', $e->getMessage());
});
