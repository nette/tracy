<?php

/**
 * Test: Tracy\Debugger notices and warnings with $strictMode in console.
 * @exitCode   254
 * @httpCode   500
 * @outputMatchFile Debugger.strict.console.expect
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::$strictMode = TRUE;
Debugger::enable();

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
