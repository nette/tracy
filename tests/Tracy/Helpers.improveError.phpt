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


// reading
test(function () use ($obj) {
	@$val = $obj->publicX;
	$message = Helpers::improveError(error_get_last()['message']);
	Assert::same('Undefined property: TestClass::$publicX, did you mean $public?', $message);
});

test(function () use ($obj) { // suggest only non-static property
	@$val = $obj->publicStaticX;
	$message = Helpers::improveError(error_get_last()['message']);
	Assert::same('Undefined property: TestClass::$publicStaticX', $message);
});

test(function () use ($obj) { // suggest only public property
	@$val = $obj->protectedX;
	$message = Helpers::improveError(error_get_last()['message']);
	Assert::same('Undefined property: TestClass::$protectedX', $message);
});


// variables
test(function () use ($obj) {
	$abcd = 1;
	@$val = $abc;
	$message = Helpers::improveError(error_get_last()['message'], get_defined_vars());
	Assert::same('Undefined variable $abc, did you mean $abcd?', $message);
});
