<?php

/**
 * Test: Tracy\Debugger notices and warnings and shut-up operator.
 * @outputMatch
 */

declare(strict_types=1);

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
header('Content-Type: text/plain; charset=utf-8');

Debugger::enable();

@mktime(); // E_DEPRECATED
@$x++; // E_NOTICE
@min(1); // E_WARNING
@require __DIR__ . '/fixtures/E_COMPILE_WARNING.php'; // E_COMPILE_WARNING
