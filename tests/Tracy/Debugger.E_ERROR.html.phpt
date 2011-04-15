<?php

/**
 * Test: Nette\Diagnostics\Debugger E_ERROR in HTML.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = FALSE;
Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

function shutdown() {
	Assert::match(file_get_contents(__DIR__ . '/Debugger.E_ERROR.html.expect'), ob_get_clean());
	die(0);
}
ob_start();
Debugger::$onFatalError[] = 'shutdown';


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
	missing_funcion();
}


first(10, 'any string');
