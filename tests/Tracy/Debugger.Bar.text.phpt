<?php

/**
 * Test: Tracy\Debugger Bar in non-HTML mode.
 *
 * @author     David Grudl
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();

register_shutdown_function(function(){
	Assert::same('', ob_get_clean());
});
ob_start();
