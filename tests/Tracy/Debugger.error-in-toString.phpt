<?php

/**
 * Test: Tracy\Debugger error in toString.
 *
 * @author     David Grudl
 * @httpCode   500
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

register_shutdown_function(function() {
	Assert::match('%A%<title>User Error</title><!-- Test::__toString -->%A%', ob_get_clean());
	die(0);
});
ob_start();


class Test
{
	function __toString()
	{
		trigger_error(__METHOD__, E_USER_ERROR);
	}
}


echo new Test;
