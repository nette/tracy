<?php

/**
 * Test: Tracy\Debugger Bar in non-HTML mode.
 * @outputMatch
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
Debugger::enable();
