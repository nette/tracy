<?php

/**
 * Test: Tracy\Debugger::barDump()
 * @outputMatch OK!
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();
Debugger::getBar()->disable();

register_shutdown_function(function() {
	Assert::same('', ob_get_clean());
	echo 'OK!'; // prevents PHP bug #62725
});
ob_start();

Debugger::barDump('<a href="#">test</a>', 'String');
