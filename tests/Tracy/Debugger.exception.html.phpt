<?php

/**
 * Test: Tracy\Debugger exception in HTML.
 *
 * @author     David Grudl
 * @httpCode   500
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

register_shutdown_function(function(){
	Assert::match(file_get_contents(__DIR__ . '/Debugger.exception.html.expect'), ob_get_clean());
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
	throw new Exception('The my exception', 123);
}


define('MY_CONST', 123);

first(10, 'any string');
