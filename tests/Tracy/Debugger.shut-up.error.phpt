<?php

/**
 * Test: Nette\Diagnostics\Debugger errors and shut-up operator.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = TRUE;
Debugger::$productionMode = FALSE;

Debugger::enable();

function shutdown() {
	Assert::match("exception 'Nette\FatalErrorException' with message 'Call to undefined function missing_funcion()' in %a%:%d%
Stack trace:
#0 [internal function]: %ns%Debugger::_shutdownHandler()
#1 {main}
", ob_get_clean());
	die(0);
}
ob_start();
Debugger::$onFatalError[] = 'shutdown';


@missing_funcion();
