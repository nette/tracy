<?php

/**
 * Test: Tracy\Debugger errors and shut-up operator.
 *
 * @author     David Grudl
 * @exitCode   255
 * @httpCode   500
 * @outputMatch exception 'Tracy\ErrorException' with message 'Call to undefined function missing_funcion()' in %A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();

@missing_funcion();
