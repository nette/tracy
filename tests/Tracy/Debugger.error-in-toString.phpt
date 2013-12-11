<?php

/**
 * Test: Tracy\Debugger error in toString.
 *
 * @author     David Grudl
 * @httpCode   500
 * @exitCode   254
 * @outputMatch %A%<title>User Error</title><!-- Test::__toString -->%A%
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bluescreen is not rendered in CLI mode');
}


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

class Test
{
	function __toString()
	{
		trigger_error(__METHOD__, E_USER_ERROR);
	}
}


echo new Test;
