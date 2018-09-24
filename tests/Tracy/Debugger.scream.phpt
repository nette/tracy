<?php

/**
 * Test: Tracy\Debugger notices and warnings in scream mode.
 * @outputMatchFile expected/Debugger.scream.expect
 */

declare(strict_types=1);

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
Debugger::$scream = true;
header('Content-Type: text/plain; charset=utf-8');

Debugger::enable();

@mktime(); // E_DEPRECATED
@$x++; // E_NOTICE
@min(1); // E_WARNING
@require __DIR__ . '/fixtures/E_COMPILE_WARNING.php'; // E_COMPILE_WARNING (not working)
