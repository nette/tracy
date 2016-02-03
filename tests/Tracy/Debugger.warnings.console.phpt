<?php

/**
 * Test: Tracy\Debugger notices and warnings in console.
 */

use Tracy\Debugger;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

ob_start();
Debugger::enable();


function first($arg1, $arg2)
{
	second(TRUE, FALSE);
}


function second($arg1, $arg2)
{
	third([1, 2, 3]);
}


function third($arg1)
{
	mktime(); // E_STRICT in PHP 5, E_DEPRECATED in PHP 7
	PHP_MAJOR_VERSION < 7 ? mktime(0, 0, 0, 1, 23, 1978, 1) : mktime(); // E_DEPRECATED
	$x++; // E_NOTICE
	min(1); // E_WARNING
	require 'E_COMPILE_WARNING.php'; // E_COMPILE_WARNING
}

first(10, 'any string');
Assert::match("
%a%: mktime(): You should be using the time() function instead in %a% on line %d%

Deprecated: mktime(): %a%

Notice: Undefined variable: x in %a% on line %d%

Warning: %a% in %a% on line %d%

Warning: Unsupported declare 'foo' in %a% on line %d%
", ob_get_clean());
