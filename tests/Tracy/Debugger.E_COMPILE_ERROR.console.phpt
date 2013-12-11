<?php

/**
 * Test: Tracy\Debugger error in console.
 *
 * @author     David Grudl
 * @exitCode   255
 * @httpCode   500
 * @outputMatch OK!
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();

$onFatalErrorCalled = FALSE;

register_shutdown_function(function() use (& $onFatalErrorCalled) {
	Assert::true($onFatalErrorCalled);
	Assert::match(extension_loaded('xdebug') ? "
Fatal error: Cannot re-assign \$this in %a%
exception 'Tracy\\ErrorException' with message 'Cannot re-assign \$this' in %a%
Stack trace:
#0 %a%: third()
#1 %a%: second()
#2 %a%: first()
#3 {main}
" : "
Fatal error: Cannot re-assign \$this in %a%
exception 'Tracy\\ErrorException' with message 'Cannot re-assign \$this' in %a%
Stack trace:
#0 [internal function]: Tracy\\Debugger::_shutdownHandler()
#1 {main}
", ob_get_clean());
	echo 'OK!'; // prevents PHP bug #62725
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
	require 'E_COMPILE_ERROR.inc';
}


first(10, 'any string');
