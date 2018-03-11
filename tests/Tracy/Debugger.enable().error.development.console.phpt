<?php

/**
 * Test: Tracy\Debugger::enable() error.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch RuntimeException: %A%
 */

declare(strict_types=1);

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

header('Content-Type: text/plain');

Debugger::enable(Debugger::DEVELOPMENT, 'relative');
