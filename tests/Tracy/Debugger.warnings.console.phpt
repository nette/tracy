<?php

/**
 * Test: Tracy\Debugger notices and warnings in console.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
header('Content-Type: text/plain');

ob_start();
Debugger::enable();


function first($arg1, $arg2)
{
	second(true, false);
}


function second($arg1, $arg2)
{
	third([1, 2, 3]);
}


function third($arg1)
{
	mktime(); // E_DEPRECATED
	$x++; // E_NOTICE
	min(1); // E_WARNING
	require __DIR__ . '/fixtures/E_COMPILE_WARNING.php'; // E_COMPILE_WARNING
}


first(10, 'any string');
Assert::match(<<<'XX'

Deprecated: mktime(): You should be using the time() function instead in %a% on line %d%

Notice: Undefined variable: x in %a% on line %d%

Warning: %a% in %a% on line %d%

Warning: Unsupported declare 'foo' in %a% on line %d%
XX
, ob_get_clean());
