<?php

/**
 * Test: Tracy\Debugger::enable() error.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch %A%<title>Server Error</title>%A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('HTML is not rendered in CLI mode');
}

Debugger::enable(Debugger::PRODUCTION, 'relative');
