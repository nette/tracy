<?php

/**
 * Test: Tracy\Debugger Bar in non-HTML mode.
 * @outputMatch
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
header('Content-Type: text/plain');

Debugger::enable();
