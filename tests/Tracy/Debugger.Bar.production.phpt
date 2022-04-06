<?php

/**
 * Test: Tracy\Debugger Bar in production mode.
 * @outputMatch
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = true;
setHtmlMode();

Debugger::enable();
