<?php

/**
 * Test: Tracy\Debugger::_exceptionHandler() error.
 * @exitCode   254
 * @httpCode   500
 * @outputMatch exception 'Exception' in%A%Unable to log error.%A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

header('Content-Type: text/plain');

Debugger::enable(Debugger::DEVELOPMENT);
Debugger::$logDirectory = 'unknown';
throw new Exception;
