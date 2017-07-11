<?php

/**
 * Test: Tracy\Debugger Bar in production mode.
 * @outputMatch
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = true;
header('Content-Type: text/html');

Debugger::enable();
