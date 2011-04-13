<?php

/**
 * Test: Nette\Diagnostics\Debugger E_RECOVERABLE_ERROR error.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = FALSE;
Debugger::$productionMode = FALSE;

Debugger::enable();



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
	Assert::exception('Nette\FatalErrorException', 'Argument 1 passed to TestClass::test1() must be an array, string given, called in %a%', $e );
}

try {
	// Invalid argument #2
	$obj->test2('hello');
	Assert::fail('Expected exception');
} catch (Exception $e) {
	Assert::exception('Nette\FatalErrorException', 'Argument 1 passed to TestClass::test2() must be an instance of TestClass, string given, called in %a%', $e );
}

try {
	// Invalid toString
	echo $obj;
	Assert::fail('Expected exception');
} catch (Exception $e) {
	Assert::exception('Nette\FatalErrorException', 'Method TestClass::__toString() must return a string value', $e );
}
