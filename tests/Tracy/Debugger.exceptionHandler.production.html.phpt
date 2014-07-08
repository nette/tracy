<?php

/**
 * Test: Tracy\Debugger::exceptionHandler() error.
 * @exitCode   254
 * @httpCode   500
 * @outputMatch %A%<title>Server Error</title>%A%Unable to log error%A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('HTML is not rendered in CLI mode');
}

Debugger::enable(Debugger::PRODUCTION);
Debugger::$logDirectory = 'unknown';
throw new Exception;
