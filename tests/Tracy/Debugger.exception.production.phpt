<?php

/**
 * Test: Tracy\Debugger exception in production mode.
 * @httpCode   500
 * @exitCode   255
 * @outputMatch %A%<h1>Server Error</h1>%A%
 */

use Tracy\Debugger;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Error page is not rendered in CLI mode');
}


Debugger::$productionMode = TRUE;
header('Content-Type: text/html');

Debugger::enable();

throw new Exception('The my exception', 123);
