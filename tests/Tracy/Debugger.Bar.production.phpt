<?php

/**
 * Test: Tracy\Debugger Bar in production mode.
 *
 * @author     David Grudl
 * @outputMatch
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = TRUE;
header('Content-Type: text/html');

Debugger::enable();
