<?php

/**
 * Test: Tracy\Debugger::enable() error.
 * @exitCode   254
 * @httpCode   500
 * @outputMatch RuntimeException: %A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

header('Content-Type: text/plain');

Debugger::enable(Debugger::DEVELOPMENT, 'relative');
