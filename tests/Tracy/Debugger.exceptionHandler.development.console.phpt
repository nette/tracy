<?php

/**
 * Test: Tracy\Debugger::exceptionHandler() error.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch Exception in%A%Unable to log error: %A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

header('Content-Type: text/plain');

Debugger::enable(Debugger::DEVELOPMENT);
Debugger::$logDirectory = 'unknown';
throw new Exception;
