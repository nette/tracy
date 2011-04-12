<?php

/**
 * Test: Nette\Debug notices and warnings with $strictMode in console.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



Debug::$consoleMode = TRUE;
Debug::$productionMode = FALSE;

Debug::$strictMode = TRUE;
Debug::enable();

function shutdown() {
	Assert::match("exception 'FatalErrorException' with message 'Undefined variable: x' in %a%
Stack trace:
#0 %a%: %ns%Debug::_errorHandler(8, '%a%', '%a%', %a%, Array)
#1 %a%: third(Array)
#2 %a%: second(true, false)
#3 %a%: first(10, 'any string')
#4 {main}
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
	$x++;
}


first(10, 'any string');
