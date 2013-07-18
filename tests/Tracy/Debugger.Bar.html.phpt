<?php

/**
 * Test: Tracy\Debugger Bar in HTML.
 *
 * @author     David Grudl
 * @output     %A%<!-- Nette Debug Bar -->%A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip();
}


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();
