<?php

/**
 * Test: Tracy\Debugger autoloading.
 *
 * @author     David Grudl
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();

register_shutdown_function(function() {
	Assert::match('%A%Strict Standards: Declaration of B::test() should be compatible with A::test() in %A%', ob_get_clean());
});
ob_start();


// in this case autoloading is not triggered
include 'E_STRICT.inc';
