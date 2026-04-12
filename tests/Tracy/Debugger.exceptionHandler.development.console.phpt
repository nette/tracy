<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger::exceptionHandler() error.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch Exception in%A%Tracy is unable to log error: %A%
 */

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

Debugger::enable(Debugger::Development);
Debugger::$logDirectory = 'unknown';
throw new Exception;
