<?php

/**
 * Test: Tracy\Debugger Bar in production mode.
 *
 * @author     David Grudl
 * @outputMatch
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = TRUE;
header('Content-Type: text/html');

Debugger::enable();
