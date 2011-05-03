<?php

/**
 * Test: Nette\Diagnostics\Debugger eval error in HTML.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 * @assertCode 500
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = FALSE;
Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

function shutdown() {
	Assert::match(file_get_contents(__DIR__ . '/Debugger.error-in-eval.expect'), ob_get_clean());
	die(0);
}
Assert::handler('shutdown');


function first($user, $pass)
{
	eval('trigger_error("The my error", E_USER_ERROR);');
}


first('root', 'xxx');
