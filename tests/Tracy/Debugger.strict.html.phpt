<?php

/**
 * Test: Tracy\Debugger notices and warnings with $strictMode in HTML.
 *
 * @author     David Grudl
 * @httpCode   500
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::$strictMode = TRUE;
Debugger::enable();

register_shutdown_function(function(){
	Assert::match(file_get_contents(__DIR__ . '/Debugger.strict.html.expect'), ob_get_clean());
	die(0);
});
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
	$x++;
}


first(10, 'any string');
