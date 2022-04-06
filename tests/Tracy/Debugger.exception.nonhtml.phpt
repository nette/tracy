<?php

/**
 * Test: Tracy\Debugger exception in non-HTML.
 * @exitCode   255
 * @httpCode   500
 * @outputMatchFile expected/Debugger.exception.nonhtml.expect
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
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


first(10, 'any string');
