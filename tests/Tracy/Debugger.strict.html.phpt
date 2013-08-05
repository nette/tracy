<?php

/**
 * Test: Tracy\Debugger notices and warnings with $strictMode in HTML.
 *
 * @author     David Grudl
 * @httpCode   500
 * @exitCode   254
 * @outputMatchFile Debugger.strict.html.expect
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bluescreen is not rendered in CLI mode');
}


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

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
