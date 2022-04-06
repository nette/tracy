<?php

/**
 * Test: Tracy\Debugger notices and warnings with $strictMode in HTML.
 * @httpCode   500
 * @exitCode   255
 * @outputMatchFile expected/Debugger.strict.html.expect
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bluescreen is not rendered in CLI mode');
}


Debugger::$productionMode = false;
setHtmlMode();

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
