<?php

/**
 * Test: Tracy\Debugger exception in HTML.
 * @httpCode   500
 * @exitCode   255
 * @outputMatchFile expected/Debugger.exception.html.expect
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bluescreen is not rendered in CLI mode');
}


Debugger::$productionMode = false;
header('Content-Type: text/html');

Debugger::enable();


function first($arg1, $arg2)
{
	second(true, false);
}


function second($arg1, $arg2)
{
	third([1, 2, 3]);
}


function third($arg1)
{
	throw new Exception('The my exception', 123);
}


define('MY_CONST', 123);
echo @$undefined;
first(10, 'any string');
