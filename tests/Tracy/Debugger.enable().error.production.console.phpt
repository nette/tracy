<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger::enable() error.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch
 */

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

Debugger::enable(Debugger::Production, 'relative');
