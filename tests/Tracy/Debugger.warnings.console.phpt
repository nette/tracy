<?php

/**
 * Test: Tracy\Debugger notices and warnings in console.
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();


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
	min(1); // E_WARNING
	require 'E_COMPILE_WARNING.inc'; // E_COMPILE_WARNING
}

ob_start();
first(10, 'any string');
Assert::match("
Strict Standards: mktime(): You should be using the time() function instead in %a% on line %d%

Deprecated: mktime(): The is_dst parameter is deprecated in %a% on line %d%

Notice: Undefined variable: x in %a% on line %d%

Warning: %a% in %a% on line %d%

Warning: Unsupported declare 'foo' in %a% on line %d%
", ob_get_clean());
