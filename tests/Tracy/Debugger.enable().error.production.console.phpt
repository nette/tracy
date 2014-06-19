<?php

/**
 * Test: Tracy\Debugger::enable() error.
 * @exitCode   254
 * @httpCode   500
 * @outputMatch ERROR: application encountered an error and can not continue.
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

header('Content-Type: text/plain');

Debugger::enable(Debugger::PRODUCTION, 'relative');
