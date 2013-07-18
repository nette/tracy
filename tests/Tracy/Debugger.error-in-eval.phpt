<?php

/**
 * Test: Tracy\Debugger eval error in HTML.
 *
 * @author     David Grudl
 * @httpCode   500
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

register_shutdown_function(function() {
	Assert::matchFile(__DIR__ . '/Debugger.error-in-eval.expect', ob_get_clean());
	die(0);
});
ob_start();


function first($user, $pass)
{
	eval('trigger_error("The my error", E_USER_ERROR);');
}


first('root', 'xxx');
