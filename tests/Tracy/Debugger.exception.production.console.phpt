<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger exception in production & console mode.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch
 */

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = true;
Debugger::enable();

throw new Exception('The my exception', 123);
