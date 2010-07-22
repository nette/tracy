<?php

/**
 * Test: Nette\Debug notices and warnings in HTML.
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
	mktime(); // E_STRICT
	mktime(0, 0, 0, 1, 23, 1978, 1); // E_DEPRECATED
	$x++; // E_NOTICE
	rename('..', '..'); // E_WARNING
	require 'E_COMPILE_WARNING.inc'; // E_COMPILE_WARNING
}


first(10, 'any string');



__halt_compiler() ?>

------EXPECT------

Warning: Unsupported declare 'foo' in %a% on line %d%

%A%<div id="nette-debug-errors"><h1>Errors</h1> <div class="nette-inner"> <table> <tr class=""> <td><pre>PHP Strict standards: mktime(): You should be using the time() function instead in %a%:%d%</pre></td> </tr> <tr class="nette-alt"> <td><pre>PHP Deprecated: mktime(): The is_dst parameter is deprecated in %a%:%d%</pre></td> </tr> <tr class=""> <td><pre>PHP Notice: Undefined variable: x in %a%:%d%</pre></td> </tr> <tr class="nette-alt"> <td><pre>PHP Warning: rename(..,..): Pøístup byl odepøen. (code: 5) in %a%:%d%</pre></td> </tr> </table>%A%
