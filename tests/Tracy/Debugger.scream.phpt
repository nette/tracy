<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger notices and warnings in scream mode.
 * @outputMatchFile expected/Debugger.scream.expect
 */

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
Debugger::$scream = true;
Debugger::enable();

@$x = &pi(); // @ E_NOTICE muted on purpose
@hex2bin('a'); // @ E_WARNING muted on purpose
@require __DIR__ . '/fixtures/E_COMPILE_WARNING.php'; // @ E_COMPILE_WARNING muted on purpose
// E_COMPILE_WARNING is handled in shutdownHandler() and does not know that @ was used
