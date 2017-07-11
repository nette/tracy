<?php

/**
 * Test: Tracy\Debugger E_ERROR in production & console mode.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = true;
header('Content-Type: text/plain');

Debugger::enable();

missing_function();
