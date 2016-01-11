<?php

/**
 * Test: Tracy\Debugger Bar disabled.
 * @outputMatch
 */

use Tracy\Debugger;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


Debugger::$productionMode = FALSE;
Debugger::$showBar = FALSE;
header('Content-Type: text/html');

Debugger::enable();
