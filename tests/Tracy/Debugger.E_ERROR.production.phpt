<?php

/**
 * Test: Tracy\Debugger E_ERROR in production mode.
 *
 * @author     David Grudl
 * @assertCode 500
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = TRUE;
header('Content-Type: text/html');

Debugger::enable();

Debugger::$onFatalError[] = function() {
	Assert::match('%A%<h1>Server Error</h1>%A%', ob_get_clean());
	die(0);
};
ob_start();


missing_funcion();
