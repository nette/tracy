<?php

/**
 * Test: Tracy\Debugger notices and warnings in scream mode.
 *
 * @author     David Grudl
 * @outputMatchFile Debugger.scream.expect
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
Debugger::$scream = TRUE;
header('Content-Type: text/plain; charset=utf-8');

Debugger::enable();

@mktime(); // E_STRICT
@mktime(0, 0, 0, 1, 23, 1978, 1); // E_DEPRECATED
@$x++; // E_NOTICE
@min(1); // E_WARNING
@require 'E_COMPILE_WARNING.inc'; // E_COMPILE_WARNING (not working)
