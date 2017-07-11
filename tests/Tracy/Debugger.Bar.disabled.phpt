<?php

/**
 * Test: Tracy\Debugger Bar disabled.
 * @outputMatch
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


Debugger::$productionMode = false;
Debugger::$showBar = false;
header('Content-Type: text/html');

Debugger::enable();
