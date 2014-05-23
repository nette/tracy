<?php

/**
 * Test: Tracy\Debugger Bar in non-HTML mode.
 * @outputMatch
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();
