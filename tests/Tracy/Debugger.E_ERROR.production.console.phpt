<?php

/**
 * Test: Tracy\Debugger E_ERROR in production & console mode.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = TRUE;
header('Content-Type: text/plain');

Debugger::enable();

missing_function();
