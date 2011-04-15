<?php

/**
 * Test: Nette\Diagnostics\Debugger E_ERROR in console.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = TRUE;
Debugger::$productionMode = FALSE;

Debugger::enable();

function shutdown() {
	Assert::match("
Fatal error: Call to undefined function missing_funcion() in %a%
exception 'Nette\FatalErrorException' with message 'Call to undefined function missing_funcion()' in %a%
Stack trace:
#0 [internal function]: %ns%Debugger::_shutdownHandler()
#1 {main}
", ob_get_clean());
	die(0);
}
ob_start();
Debugger::$onFatalError[] = 'shutdown';


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
