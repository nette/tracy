<?php

/**
 * Test: Tracy\Debugger suggestions
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Bridges\Nette\Bridge;

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


test('', function () {
	try {
		TestClass::publicMethodX();
	} catch (Nette\MemberAccessException $e) {
	}

	$action = Bridge::renderMemberAccessException($e);
	Assert::same('Call to undefined static method TestClass::publicMethodX().', $e->getMessage());
	Assert::null($action);
});

test('', function () use ($obj) {
	try {
		$obj->publicMethodX();
	} catch (Nette\MemberAccessException $e) {
	}

	$action = Bridge::renderMemberAccessException($e);
	Assert::same('Call to undefined method TestClass::publicMethodX(), did you mean publicMethod()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.smartobject.phpt&line=%d%&search=-%3EpublicMethodX%28&replace=-%3EpublicMethod%28', $action['link']);
	Assert::same('fix it', $action['label']);
});

test('suggest static method', function () use ($obj) {
	try {
		$obj->publicMethodStaticX();
	} catch (Nette\MemberAccessException $e) {
	}

	$action = Bridge::renderMemberAccessException($e);
	Assert::same('Call to undefined method TestClass::publicMethodStaticX(), did you mean publicMethodStatic()?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.smartobject.phpt&line=%d%&search=-%3EpublicMethodStaticX%28&replace=-%3EpublicMethodStatic%28', $action['link']);
	Assert::same('fix it', $action['label']);
});

test('', function () use ($obj) {
	try {
		$val = $obj->publicX;
	} catch (Nette\MemberAccessException $e) {
	}

	$action = Bridge::renderMemberAccessException($e);
	Assert::same('Cannot read an undeclared property TestClass::$publicX, did you mean $public?', $e->getMessage());
	Assert::match('editor://fix/?file=%a%Helpers.improveException.smartobject.phpt&line=%d%&search=-%3EpublicX&replace=-%3Epublic', $action['link']);
	Assert::same('fix it', $action['label']);
});
