<?php

/**
 * Test: Tracy\Debugger error in console.
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
	Assert::match(extension_loaded('xdebug') ? '
Fatal error: Cannot re-assign $this in %a%
ErrorException: Cannot re-assign $this in %a%
Stack trace:
#0 %a%: third()
#1 %a%: second()
#2 %a%: first()
#3 {main}
Unable to log error: Logging directory is not specified.
' : '
Fatal error: Cannot re-assign $this in %a%
ErrorException: Cannot re-assign $this in %a%
Stack trace:
#0 [internal function]: Tracy\\Debugger::shutdownHandler()
#1 {main}
Unable to log error: Logging directory is not specified.
', ob_get_clean());
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
	require __DIR__ . '/fixtures/E_COMPILE_ERROR.php';
}


first(10, 'any string');
