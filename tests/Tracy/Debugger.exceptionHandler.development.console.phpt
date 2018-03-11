<?php

/**
 * Test: Tracy\Debugger::exceptionHandler() error.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch Exception in%A%Unable to log error: %A%
 */

declare(strict_types=1);

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

header('Content-Type: text/plain');

Debugger::enable(Debugger::DEVELOPMENT);
Debugger::$logDirectory = 'unknown';
throw new Exception;
