<?php

/**
 * Test: Tracy\Debugger E_ERROR in console.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch OK!
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
header('Content-Type: text/plain');

ob_start();
Debugger::enable();

$onFatalErrorCalled = false;

register_shutdown_function(function () use (&$onFatalErrorCalled) {
	Assert::true($onFatalErrorCalled);
	Assert::match(PHP_MAJOR_VERSION > 5 ?
"Error: Call to undefined function missing_function() in %a%
Stack trace:
#0 %a%: third(Array)
#1 %a%: second(true, false)
#2 %a%: first(10, 'any string')
#3 {main}
Unable to log error: Logging directory is not specified.
" : (extension_loaded('xdebug') ? '
Fatal error: Call to undefined function missing_function() in %a%
ErrorException: Call to undefined function missing_function() in %a%
Stack trace:
#0 %a%: third()
#1 %a%: second()
#2 %a%: first()
#3 {main}
Unable to log error: Logging directory is not specified.
' : '
Fatal error: Call to undefined function missing_function() in %a%
ErrorException: Call to undefined function missing_function() in %a%
Stack trace:
#0 [internal function]: Tracy\\Debugger::shutdownHandler()
#1 {main}
Unable to log error: Logging directory is not specified.
'), ob_get_clean());
	echo 'OK!'; // prevents PHP bug #62725
});


Debugger::$onFatalError[] = function () use (&$onFatalErrorCalled) {
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
