<?php

/**
 * Test: Tracy\Debugger errors and shut-up operator.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch Error%a?%: Call to undefined function missing_function() in %A%
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
Debugger::enable();

@missing_function();
