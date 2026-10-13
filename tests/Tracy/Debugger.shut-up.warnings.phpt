<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger notices and warnings and shut-up operator.
 * @outputMatch %A%Compile Warning: Unsupported declare 'foo' in %a% on line %d%
 */

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
Debugger::enable();

@$x = &pi(); // @ E_NOTICE muted on purpose
@hex2bin('a'); // @ E_WARNING muted on purpose
@require __DIR__ . '/fixtures/E_COMPILE_WARNING.php'; // @ E_COMPILE_WARNING muted on purpose
// E_COMPILE_WARNING is handled in shutdownHandler() and does not know that @ was used
