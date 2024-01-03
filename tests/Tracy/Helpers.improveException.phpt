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


test('calling', function () {
	try {
		trimx();
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::same('Call to undefined function trimx(), did you mean trim()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=trimx%28&replace=trim%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test('', function () {
	try {
		abc\trimx();
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::same('Call to undefined function trimx(), did you mean trim()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=trimx%28&replace=trim%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test('', function () {
	try {
		myFunctionx();
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::same('Call to undefined function myFunctionx(), did you mean myfunction()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=myFunctionx%28&replace=myfunction%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test('', function () {
	try {
		TestClass::publicMethodX();
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::publicMethodX(), did you mean publicMethod()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=publicMethodX%28&replace=publicMethod%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test('', function () use ($obj) {
	try {
		$obj->publicMethodX();
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::publicMethodX(), did you mean publicMethod()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=publicMethodX%28&replace=publicMethod%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test('suggest static method', function () use ($obj) {
	try {
		$obj->publicMethodStaticX();
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::publicMethodStaticX(), did you mean publicMethodStatic()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=publicMethodStaticX%28&replace=publicMethodStatic%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test('suggest only public method', function () use ($obj) {
	try {
		$obj->protectedMethodX();
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::protectedMethodX()', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});

test('do not suggest anything when accessing anonymous class', function () {
	try {
		$obj = new class {
		};
		$obj->method();
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::same('Call to undefined method class@anonymous::method()', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});


test('reading', function () use ($obj) {
	@$val = $obj->publicX;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: TestClass::$publicX, did you mean $public?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=-%3EpublicX&replace=-%3Epublic', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test('suggest only non-static property', function () use ($obj) {
	@$val = $obj->publicStaticX;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: TestClass::$publicStaticX', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});

test('suggest only public property', function () use ($obj) {
	@$val = $obj->protectedX;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: TestClass::$protectedX', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});

test('suggest only static property', function () use ($obj) {
	try {
		$val = TestClass::$publicStaticX;
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::match('Access to undeclared static property%a?% TestClass::$publicStaticX, did you mean $publicStatic?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.phpt&line=%d%&search=%3A%3A%24publicStaticX&replace=%3A%3A%24publicStatic', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test('suggest only public static property', function () use ($obj) {
	try {
		$val = TestClass::$protectedMethodX;
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::match('Access to undeclared static property%a?% TestClass::$protectedMethodX', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});

test('do not suggest anything when accessing anonymous class', function () {
	$obj = new class {
	};
	@$val = $obj->property;
	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: class@anonymous::$property', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});

test('do not suggest anything when accessing anonymous class', function () {
	try {
		$obj = new class {
		};
		@$val = $obj::$property;
	} catch (Error $e) {
	}

	$e = new ErrorException(error_get_last()['message'], 0, error_get_last()['type']);
	Helpers::improveException($e);
	Assert::same('Undefined property: class@anonymous::$property', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});


test('callable error: ignore syntax mismatch', function () {
	try {
		(fn(callable $a) => null)(null);
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::match('{closure}(): Argument #1 ($a) must be of type callable, null given, called in %a%', $e->getMessage());
});

test('callable error: typo in class name', function () {
	try {
		(fn(callable $a) => null)([PhpTokn::class, 'tokenize']);
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::match("{closure}(): Argument #1 (\$a) must be of type callable, but class 'PhpTokn' does not exist, called in %a%", $e->getMessage());
});

test('callable error: typo in class name', function () {
	try {
		(fn(callable $a) => null)('PhpTokn::tokenize');
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::match("{closure}(): Argument #1 (\$a) must be of type callable, but class 'PhpTokn' does not exist, called in %a%", $e->getMessage());
});

test('callable error: typo in method name', function () {
	try {
		(fn(callable $a) => null)([PhpToken::class, 'tokenze']);
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::match('{closure}(): Argument #1 ($a) must be of type callable, but method PhpToken::tokenze() does not exist (did you mean tokenize?), called in %a%', $e->getMessage());
});

test('callable error: typo in method name', function () {
	try {
		(fn(callable $a) => null)('PhpToken::tokenze');
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::match('{closure}(): Argument #1 ($a) must be of type callable, but method PhpToken::tokenze() does not exist (did you mean tokenize?), called in %a%', $e->getMessage());
});

test('callable error: typo in function name', function () {
	try {
		(fn(callable $a) => null)('trm');
	} catch (Error $e) {
	}

	Helpers::improveException($e);
	Assert::match("{closure}(): Argument #1 (\$a) must be of type callable, but function 'trm' does not exist (did you mean trim?), called in %a%", $e->getMessage());
});
