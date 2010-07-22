<?php

/**
 * Test: Nette\Debug E_RECOVERABLE_ERROR error.
 *
 * @author     David Grudl
 * @category   Nette
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
	T::note("Invalid argument #1");
	$obj->test1('hello');
} catch (Exception $e) {
	T::dump( $e );
}

try {
	T::note("Invalid argument #2");
	$obj->test2('hello');
} catch (Exception $e) {
	T::dump( $e );
}

try {
	T::note("Invalid toString");
	echo $obj;
} catch (Exception $e) {
	T::dump( $e );
}



__halt_compiler() ?>

------EXPECT------
Invalid argument #1

Exception FatalErrorException: Argument 1 passed to TestClass::test1() must be an array, string given, called in %a%

Invalid argument #2

Exception FatalErrorException: Argument 1 passed to TestClass::test2() must be an instance of TestClass, string given, called in %a%

Invalid toString

Exception FatalErrorException: Method TestClass::__toString() must return a string value
