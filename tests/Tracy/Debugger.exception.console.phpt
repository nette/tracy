<?php

/**
 * Test: Nette\Diagnostics\Debugger exception in console.
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
	Assert::match("exception 'Exception' with message 'The my exception' in %a%
Stack trace:
#0 %a%: third(Array)
#1 %a%: second(true, false)
#2 %a%: first(10, 'any string')
#3 {main}
", ob_get_clean());
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
	throw new Exception('The my exception', 123);
}


first(10, 'any string');
