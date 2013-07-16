<?php

/**
 * Test: Tracy\Debugger notices and warnings with $strictMode in console.
 *
 * @author     David Grudl
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::$strictMode = TRUE;
Debugger::enable();

Debugger::$onFatalError[] = function() {
	Assert::match("exception 'Tracy\\ErrorException' with message 'Undefined variable: x' in %a%
Stack trace:
#0 %a%: Nette\\Diagnostics\\Debugger::_errorHandler(8, '%a%', '%a%', %a%, Array)
#1 %a%: third(Array)
#2 %a%: second(true, false)
#3 %a%: first(10, 'any string')
#4 {main}
", ob_get_clean());
	die(0);
};
ob_start();


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
	$x++;
}


first(10, 'any string');
