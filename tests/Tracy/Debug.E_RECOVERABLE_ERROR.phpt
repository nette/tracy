<?php

/**
 * Test: Nette\Debug E_RECOVERABLE_ERROR error.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;

Debug::enable();



class TestClass
{

	function test1(array $val)
	{
	}


	function test2(TestClass $val)
	{
	}


	function __toString()
	{
		return FALSE;
	}


}


$obj = new TestClass;

try {
	// Invalid argument #1
	$obj->test1('hello');
	Assert::fail('Expected exception');
} catch (Exception $e) {
	Assert::exception('FatalErrorException', 'Argument 1 passed to TestClass::test1() must be an array, string given, called in %a%', $e );
}

try {
	// Invalid argument #2
	$obj->test2('hello');
	Assert::fail('Expected exception');
} catch (Exception $e) {
	Assert::exception('FatalErrorException', 'Argument 1 passed to TestClass::test2() must be an instance of TestClass, string given, called in %a%', $e );
}

try {
	// Invalid toString
	echo $obj;
	Assert::fail('Expected exception');
} catch (Exception $e) {
	Assert::exception('FatalErrorException', 'Method TestClass::__toString() must return a string value', $e );
}
