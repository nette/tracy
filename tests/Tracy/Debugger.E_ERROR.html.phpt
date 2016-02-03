<?php

/**
 * Test: Tracy\Debugger E_ERROR in HTML.
 * @httpCode   500
 * @exitCode   255
 * @outputMatch OK!
 */

use Tracy\Debugger;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bluescreen is not rendered in CLI mode');
}


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

ob_start();
Debugger::enable();


$onFatalErrorCalled = FALSE;

register_shutdown_function(function () use (& $onFatalErrorCalled) {
	Assert::true($onFatalErrorCalled);
	$output = ob_get_clean();
	Assert::same(1, substr_count($output, '<!-- Tracy Debug Bar'));
	Assert::matchFile(__DIR__ . '/Debugger.E_ERROR.html' . (PHP_MAJOR_VERSION > 5 ? '' : (extension_loaded('xdebug') ? '.xdebug' : '.php5')) . '.expect', $output);
	echo 'OK!'; // prevents PHP bug #62725
});


Debugger::$onFatalError[] = function () use (& $onFatalErrorCalled) {
	$onFatalErrorCalled = TRUE;
};



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
	missing_function();
}


first(10, 'any string');
