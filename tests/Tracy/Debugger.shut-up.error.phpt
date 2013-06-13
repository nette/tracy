<?php

/**
 * Test: Tracy\Debugger errors and shut-up operator.
 *
 * @author     David Grudl
 * @package    Tracy
 */

use Tracy\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();

Debugger::$onFatalError[] = function() {
	Assert::match(extension_loaded('xdebug') ? "exception 'Tracy\\ErrorException' with message 'Call to undefined function missing_funcion()' in %a%:%d%
Stack trace:
#0 {main}
" : "exception 'Tracy\\ErrorException' with message 'Call to undefined function missing_funcion()' in %a%:%d%
Stack trace:
#0 [internal function]: %ns%Debugger::_shutdownHandler()
#1 {main}
", ob_get_clean());
	die(0);
};
ob_start();


@missing_funcion();
