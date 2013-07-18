<?php

/**
 * Test: Tracy\Debugger Bar in non-HTML mode.
 *
 * @author     David Grudl
 * @outputMatch
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();
