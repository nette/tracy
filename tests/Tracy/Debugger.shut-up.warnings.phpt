<?php

/**
 * Test: Tracy\Debugger notices and warnings and shut-up operator.
 * @outputMatch %A%PHP Compile Warning: Unsupported declare 'foo' in %a% on line %d%
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
header('Content-Type: text/plain; charset=utf-8');

Debugger::enable();

@$x = &pi(); // E_NOTICE
@hex2bin('a'); // E_WARNING
@require __DIR__ . '/fixtures/E_COMPILE_WARNING.php'; // E_COMPILE_WARNING
// E_COMPILE_WARNING is handled in shutdownHandler() and does not know that @ was used
