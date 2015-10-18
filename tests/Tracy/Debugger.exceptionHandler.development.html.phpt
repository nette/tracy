<?php

/**
 * Test: Tracy\Debugger::exceptionHandler() error.
 * @exitCode   254
 * @httpCode   500
 * @outputMatch %A%<title>Exception: </title>%A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('HTML is not rendered in CLI mode');
}

Debugger::enable(Debugger::DEVELOPMENT);
Debugger::$logDirectory = 'unknown';
throw new Exception;
