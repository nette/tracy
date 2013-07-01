<?php

/**
 * Test: Tracy\Debugger E_ERROR in HTML.
 *
 * @author     David Grudl
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

Debugger::$onFatalError[] = function() {
	Assert::match(file_get_contents(__DIR__ . (extension_loaded('xdebug') ? '/Debugger.E_ERROR.html.xdebug.expect' : '/Debugger.E_ERROR.html.expect')), ob_get_clean());
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
