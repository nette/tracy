<?php

/**
 * Test: Nette\Debug notices and warnings in console.
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



__halt_compiler() ?>

------EXPECT------

Notice: Undefined variable: x in %a%

Warning: rename(..,..): %a%
