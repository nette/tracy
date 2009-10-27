<?php

/**
 * Test: Nette\Debug notices with $strictMode in console.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



Debug::$consoleMode = TRUE;
Debug::$productionMode = FALSE;

Debug::$strictMode = TRUE;
Debug::enable();



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

output('after');



__halt_compiler();

------EXPECT------
exception 'FatalErrorException' with message 'Undefined variable: x' in %a%
Stack trace:
#0 %a%: %ns%Debug::_errorHandler(8, '%a%', '%a%', %a%, Array)
#1 %a%: third(Array)
#2 %a%: second(true, false)
#3 %a%: first(10, 'any string')
#4 {main}
Report generated at %a%
PHP %a%
Nette Framework %a%
