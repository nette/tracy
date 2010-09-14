<?php

/**
 * Test: Nette\Debug E_ERROR in console.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = TRUE;
Debug::$productionMode = FALSE;

Debug::enable();

function shutdown() {
	Assert::match("
Fatal error: Call to undefined function missing_funcion() in %a%
exception 'FatalErrorException' with message 'Call to undefined function missing_funcion()' in %a%
Stack trace:
#0 [internal function]: %ns%Debug::_shutdownHandler()
#1 {main}
", ob_get_clean());
	die(0);
}
Assert::handler('shutdown');



function first($arg1, $arg2)
{
	second(TRUE, FALSE);
}


function second($arg1, $arg2)
{
	third(array(1, 2, 3));
}


function third($arg1)
{
	missing_funcion();
}


first(10, 'any string');
