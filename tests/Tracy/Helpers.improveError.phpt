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


$obj = new TestClass;


test('reading', function () use ($obj) {
	@$val = $obj->publicX;
	$message = Helpers::improveError(error_get_last()['message']);
	Assert::same('Undefined property: TestClass::$publicX, did you mean $public?', $message);
});

test('suggest only non-static property', function () use ($obj) {
	@$val = $obj->publicStaticX;
	$message = Helpers::improveError(error_get_last()['message']);
	Assert::same('Undefined property: TestClass::$publicStaticX', $message);
});

test('suggest only public property', function () use ($obj) {
	@$val = $obj->protectedX;
	$message = Helpers::improveError(error_get_last()['message']);
	Assert::same('Undefined property: TestClass::$protectedX', $message);
});

test('do not suggest anything when accessing anonymous class', function () {
	$obj = new class {
	};
	@$val = $obj->property;
	$message = Helpers::improveError(error_get_last()['message']);
	Assert::same('Undefined property: class@anonymous::$property', $message);
});
