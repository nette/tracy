<?php

/**
 * Test: Tracy\Debugger suggestions
 * @phpversion 7
 */

use Tester\Assert;
use Tracy\Helpers;


require __DIR__ . '/../bootstrap.php';


class TestClass
{
	use Nette\SmartObject;

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


test(function () {
	try {
		TestClass::publicMethodX();
	} catch (Nette\MemberAccessException $e) {
	}
	Helpers::improveException($e);
	Assert::same('Call to undefined static method TestClass::publicMethodX().', $e->getMessage());
	Assert::false(isset($e->tracyAction));
});

test(function () use ($obj) {
	try {
		$obj->publicMethodX();
	} catch (Nette\MemberAccessException $e) {
	}
	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::publicMethodX(), did you mean publicMethod()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.smartobject.phpt&line=%d%&search=publicMethodX%28&replace=publicMethod%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test(function () use ($obj) { // suggest static method
	try {
		$obj->publicMethodStaticX();
	} catch (Nette\MemberAccessException $e) {
	}
	Helpers::improveException($e);
	Assert::same('Call to undefined method TestClass::publicMethodStaticX(), did you mean publicMethodStatic()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.smartobject.phpt&line=%d%&search=publicMethodStaticX%28&replace=publicMethodStatic%28', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});

test(function () use ($obj) {
	try {
		$val = $obj->publicX;
	} catch (Nette\MemberAccessException $e) {
	}
	Helpers::improveException($e);
	Assert::same('Cannot read an undeclared property TestClass::$publicX, did you mean $public?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.smartobject.phpt&line=%d%&search=-%3EpublicX&replace=-%3Epublic', $e->tracyAction['link']);
	Assert::same('fix it', $e->tracyAction['label']);
});
