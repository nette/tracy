<?php

/**
 * Test: Nette\Debug notices and warnings.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;

Debug::enable();

header('Content-Type: text/html');



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
%A%<div id="nette-debug-errors">%A%PHP Notice: Undefined variable: x in %A%PHP Warning: rename(..,..): %A%
