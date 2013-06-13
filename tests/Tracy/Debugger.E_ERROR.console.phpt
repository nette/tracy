<?php

/**
 * Test: Tracy\Debugger E_ERROR in console.
 *
 * @author     David Grudl
 * @package    Tracy
 */

use Tracy\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();

Debugger::$onFatalError[] = function() {
	Assert::match(extension_loaded('xdebug') ? "
Fatal error: Call to undefined function missing_funcion() in %a%
exception 'Tracy\\ErrorException' with message 'Call to undefined function missing_funcion()' in %a%
Stack trace:
#0 %a%: third()
#1 %a%: second()
#2 %a%: first()
#3 {main}
" : "
Fatal error: Call to undefined function missing_funcion() in %a%
exception 'Tracy\\ErrorException' with message 'Call to undefined function missing_funcion()' in %a%
Stack trace:
#0 [internal function]: %ns%Debugger::_shutdownHandler()
#1 {main}
", ob_get_clean());
	die(0);
};
ob_start();


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
