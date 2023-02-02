<?php

/**
 * Test: Tracy\Debugger::exceptionHandler() error.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch %A%<title>Server Error</title>%A%Tracy is unable to log error%A%
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('HTML is not rendered in CLI mode');
}

setHtmlMode();
Debugger::enable(Debugger::Production);
Debugger::$logDirectory = 'unknown';
throw new Exception;
