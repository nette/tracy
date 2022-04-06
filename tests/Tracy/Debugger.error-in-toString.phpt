<?php

/**
 * Test: Tracy\Debugger error in toString.
 * @httpCode   500
 * @exitCode   255
 * @outputMatch %A%<title>User Error: Test::__toString</title>%A%
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bluescreen is not rendered in CLI mode');
}


Debugger::$productionMode = false;
setHtmlMode();

Debugger::enable();

class Test
{
	public function __toString()
	{
		trigger_error(__METHOD__, E_USER_ERROR);
	}
}


echo new Test;
