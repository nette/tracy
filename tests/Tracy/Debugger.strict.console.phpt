<?php

/**
 * Test: Tracy\Debugger notices and warnings with $strictMode in console.
 * @exitCode   255
 * @httpCode   500
 * @outputMatchFile expected/Debugger.strict.console.expect
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
Debugger::$strictMode = true;
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
	$x = &pi();
}


first(10, 'any string');
