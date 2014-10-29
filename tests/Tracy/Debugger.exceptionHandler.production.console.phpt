<?php

/**
 * Test: Tracy\Debugger::exceptionHandler() error.
 * @exitCode   254
 * @httpCode   500
 * @outputMatch
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

header('Content-Type: text/plain');

Debugger::enable(Debugger::PRODUCTION);
Debugger::$logDirectory = 'unknown';
throw new Exception;
