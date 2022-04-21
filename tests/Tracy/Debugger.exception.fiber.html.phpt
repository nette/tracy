<?php

/**
 * Test: Tracy\Debugger exception in HTML.
 * @phpVersion 8.1
 * @httpCode   500
 * @exitCode   255
 * @outputMatchFile expected/Debugger.exception.fiber.html.expect
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bluescreen is not rendered in CLI mode');
}


Debugger::$productionMode = false;
setHtmlMode();

Debugger::enable();


function gen1()
{
	gen2(123);
}


function gen2($a)
{
	$x = Fiber::suspend($a);
}


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
	throw new Exception('The my exception', 123);
}


$fiber = new Fiber(function () {
	gen1();
});
$fiber->start();
first($fiber, 'any string');
