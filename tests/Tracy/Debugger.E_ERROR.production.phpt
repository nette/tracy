<?php

/**
 * Test: Tracy\Debugger E_ERROR in production mode.
 *
 * @author     David Grudl
 * @httpCode   500
 * @exitCode   255
 * @outputMatch %A%<h1>Server Error</h1>%A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Helpers::skip();
}


Debugger::$productionMode = TRUE;
header('Content-Type: text/html');

Debugger::enable();

missing_funcion();
