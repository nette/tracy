<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger Bar in production mode.
 * @outputMatch
 */

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = true;
setHtmlMode();

Debugger::enable();
