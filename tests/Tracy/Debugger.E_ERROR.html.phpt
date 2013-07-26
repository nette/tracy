<?php

/**
 * Test: Tracy\Debugger E_ERROR in HTML.
 *
 * @author     David Grudl
 * @httpCode   500
 * @exitCode   255
 * @outputMatch OK!
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip();
}


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();


$onFatalErrorCalled = FALSE;

register_shutdown_function(function() use (& $onFatalErrorCalled) {
	Assert::true($onFatalErrorCalled);
	$output = ob_get_clean();
	Assert::same(1, substr_count($output, '<!-- Nette Debug Bar'));
	Assert::matchFile(__DIR__ . (extension_loaded('xdebug') ? '/Debugger.E_ERROR.html.xdebug.expect' : '/Debugger.E_ERROR.html.expect'), $output);
	echo 'OK!';
});


Debugger::$onFatalError[] = function() use (& $onFatalErrorCalled) {
	$onFatalErrorCalled = TRUE;
};
ob_start();


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
	missing_funcion();
}


first(10, 'any string');
