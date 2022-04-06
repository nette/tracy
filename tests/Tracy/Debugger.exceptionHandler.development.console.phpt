<?php

/**
 * Test: Tracy\Debugger::exceptionHandler() error.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch Exception in%A%Tracy is unable to log error: %A%
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

Debugger::enable(Debugger::DEVELOPMENT);
Debugger::$logDirectory = 'unknown';
throw new Exception;
