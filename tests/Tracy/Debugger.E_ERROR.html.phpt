<?php

/**
 * Test: Tracy\Debugger E_ERROR in HTML.
 * @httpCode   500
 * @exitCode   255
 * @outputMatch OK!
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bluescreen is not rendered in CLI mode');
}


Debugger::$productionMode = false;
setHtmlMode();

ob_start();
Debugger::enable();


$onFatalErrorCalled = false;

register_shutdown_function(function () use (&$onFatalErrorCalled) {
	Assert::true($onFatalErrorCalled);
	$output = ob_get_clean();
	Assert::same(1, substr_count($output, '<!-- Tracy Debug Bar'));
	Assert::matchFile(__DIR__ . '/expected/Debugger.E_ERROR.html.expect', $output);
	echo 'OK!'; // prevents PHP bug #62725
});


Debugger::$onFatalError[] = function () use (&$onFatalErrorCalled) {
	// empty line
	$onFatalErrorCalled = true;
};


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
	missing_function();
}


first(10, 'any string');
