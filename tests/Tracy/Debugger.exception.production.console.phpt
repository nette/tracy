<?php

/**
 * Test: Tracy\Debugger exception in production & console mode.
 *
 * @author     David Grudl
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = TRUE;
header('Content-Type: text/plain');

Debugger::enable();

register_shutdown_function(function(){
	Assert::match('ERROR:%A%', ob_get_clean());
	die(0);
});
ob_start();


throw new Exception('The my exception', 123);
