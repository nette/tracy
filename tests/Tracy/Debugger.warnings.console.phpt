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
	$x = &pi(); // E_NOTICE
	hex2bin('a'); // E_WARNING
	require __DIR__ . '/fixtures/E_COMPILE_WARNING.php'; // E_COMPILE_WARNING
	// E_COMPILE_WARNING is handled in shutdownHandler()
}


register_shutdown_function(function () {
	Assert::match(<<<'XX'

Notice: Only variables should be assigned by reference in %a% on line %d%

Warning: hex2bin(): Hexadecimal input string must have an even length in %a% on line %d%

Warning: Unsupported declare 'foo' in %a% on line %d%
XX
	, ob_get_clean());
});


first(10, 'any string');
