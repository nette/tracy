<?php

/**
 * Test: Tracy\Debugger::exceptionHandler() error.
 * @exitCode   254
 * @httpCode   500
 * @outputMatch ERROR: application encountered an error and can not continue.%A%Unable to log error. Check if directory is writable and path is absolute.
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

header('Content-Type: text/plain');

Debugger::enable(Debugger::PRODUCTION);
Debugger::$logDirectory = 'unknown';
throw new Exception;
