<?php

/**
 * Test: Tracy\Debugger E_ERROR in production & console mode.
 *
 * @author     David Grudl
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = TRUE;
header('Content-Type: text/plain');

Debugger::enable();

Debugger::$onFatalError[] = function() {
	Assert::match('ERROR:%A%', ob_get_clean());
	die(0);
};
ob_start();


missing_funcion();
