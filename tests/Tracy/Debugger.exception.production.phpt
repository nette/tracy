<?php

/**
 * Test: Tracy\Debugger exception in production mode.
 *
 * @author     David Grudl
 * @httpCode   500
 * @exitCode   254
 * @outputMatch %A%<h1>Server Error</h1>%A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip();
}


Debugger::$productionMode = TRUE;
header('Content-Type: text/html');

Debugger::enable();

throw new Exception('The my exception', 123);
