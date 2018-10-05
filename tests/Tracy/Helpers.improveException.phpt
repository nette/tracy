<?php

/**
 * Test: Tracy\Debugger suggestions
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;


require __DIR__ . '/../bootstrap.php';


class TestClass
{
	public $public;

	public static $publicStatic;

	protected $protected;


	public function publicMethod()
	{
	}


	public static function publicMethodStatic()
	{
	}


	protected function protectedMethod()
	{
	}


	protected static function protectedMethodS()
	{
	}
}


function myFunction()
{
}


$obj = new TestClass;


// calling
test(function () {
	try {
		trimx();
	} catch (\Error $e) {
	}
	Helpers::improveException($e);
	Assert::same('Call to undefined function trimx(), did you mean trim()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=trimx%28&replace=trim%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test(function () {
	try {
		abc\trimx();
	} catch (\Error $e) {
	}
	Helpers::improveException($e);
	Assert::same('Call to undefined function trimx(), did you mean trim()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=trimx%28&replace=trim%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test(function () {
	try {
		myFunctionx();
	} catch (\Error $e) {
	}
	Helpers::improveException($e);
	Assert::same('Call to undefined function myFunctionx(), did you mean myfunction()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=myFunctionx%28&replace=myfunction%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test(function () {
	try {
		TestClass::publicMethodX();
	} catch (\Error $e) {
	}
	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::publicMethodX(), did you mean publicMethod()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=publicMethodX%28&replace=publicMethod%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test(function () use ($obj) {
	try {
		$obj->publicMethodX();
	} catch (\Error $e) {
	}
	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::publicMethodX(), did you mean publicMethod()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=publicMethodX%28&replace=publicMethod%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test(function () use ($obj) { // suggest static method
	try {
		$obj->publicMethodStaticX();
	} catch (\Error $e) {
	}
	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::publicMethodStaticX(), did you mean publicMethodStatic()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=publicMethodStaticX%28&replace=publicMethodStatic%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test(function () use ($obj) { // suggest only public method
	try {
		$obj->protectedMethodX();
	} catch (\Error $e) {
	}
	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::protectedMethodX()', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});

test(function () { // do not suggest anything when accessing anonymous class
	try {
		$obj = new class {
		};
		$obj->method();
	} catch (\Error $e) {
	}
	Helpers::improveException($e);
	Assert::same('Call to undefined method class@anonymous::method()', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});


// reading
test(function () use ($obj) {
	@$val = $obj->publicX;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: TestClass::$publicX, did you mean $public?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=-%3EpublicX&replace=-%3Epublic', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test(function () use ($obj) { // suggest only non-static property
	@$val = $obj->publicStaticX;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: TestClass::$publicStaticX', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});

test(function () use ($obj) { // suggest only public property
	@$val = $obj->protectedX;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: TestClass::$protectedX', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});

test(function () use ($obj) { // suggest only static property
	try {
		$val = TestClass::$publicStaticX;
	} catch (\Error $e) {
	}
	Helpers::improveException($e);
	Assert::same('Access to undeclared static property: TestClass::$publicStaticX, did you mean $publicStatic?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=%3A%3A%24publicStaticX&replace=%3A%3A%24publicStatic', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test(function () use ($obj) { // suggest only public static property
	try {
		$val = TestClass::$protectedMethodX;
	} catch (\Error $e) {
	}
	Helpers::improveException($e);
	Assert::same('Access to undeclared static property: TestClass::$protectedMethodX', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});

test(function () { // do not suggest anything when accessing anonymous class
	$obj = new class {
	};
	@$val = $obj->property;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: class@anonymous::$property', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});

test(function () { // do not suggest anything when accessing anonymous class
	try {
		$obj = new class {
		};
		@$val = $obj::$property;
	} catch (\Error $e) {
	}
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: class@anonymous::$property', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});


// variables
test(function () use ($obj) {
	$abcd = 1;
	@$val = $abc;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	$e->context = get_defined_vars();
	Helpers::improveException($e);
	Assert::same('Undefined variable $abc, did you mean $abcd?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=%24abc&replace=%24abcd', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});
