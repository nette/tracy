<?php

/**
 * Test: Tracy\Debugger exception in production & console mode.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = true;
header('Content-Type: text/plain');

Debugger::enable();

throw new Exception('The my exception', 123);
