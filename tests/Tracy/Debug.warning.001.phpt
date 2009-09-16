<?php

/**
 * Test: Nette\Debug notices and warnings.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;

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
	rename('..', '..');
}


first(10, 'any string');



__halt_compiler();

------EXPECT------
<br />
<b>Notice</b>:  Undefined variable: x in %a%
<br />
<b>Warning</b>:  rename(..,..) [<a href='function.rename'>function.rename</a>]: %a%
