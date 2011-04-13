<?php

/**
 * Test: Nette\Diagnostics\Debugger error in console.
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
	Assert::match("
Fatal error: Cannot re-assign \$this in %a%
exception 'Nette\FatalErrorException' with message 'Cannot re-assign \$this' in %a%
Stack trace:
#0 [internal function]: %ns%Debugger::_shutdownHandler()
#1 {main}
", ob_get_clean());
	die(0);
}
Assert::handler('shutdown');



function first($arg1, $arg2)
{
	second(TRUE, FALSE);
}


function second($arg1, $arg2)
{
	third(array(1, 2, 3));
}


function third($arg1)
{
	require 'E_COMPILE_ERROR.inc';
}


first(10, 'any string');
