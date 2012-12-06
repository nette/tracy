<?php

/**
 * Test: Nette\Diagnostics\Debugger errors and shut-up operator.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();

Debugger::$onFatalError[] = function() {
	Assert::match(extension_loaded('xdebug') ? "exception 'Nette\FatalErrorException' with message 'Call to undefined function missing_funcion()' in %a%:%d%
Stack trace:
#0 {main}
(stored in %a%)
" : "exception 'Nette\FatalErrorException' with message 'Call to undefined function missing_funcion()' in %a%:%d%
Stack trace:
#0 [internal function]: %ns%Debugger::_shutdownHandler()
#1 {main}
(stored in %a%)
", ob_get_clean());
	die(0);
};
ob_start();


@missing_funcion();
