<?php

/**
 * Test: Nette\Diagnostics\Debugger exception in production mode.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 * @assertCode 500
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = FALSE;
Debugger::$productionMode = TRUE;
header('Content-Type: text/html');

Debugger::enable();

function shutdown() {
	Assert::match('%A%<h1>Server Error</h1>%A%', ob_get_clean());
}
Assert::handler('shutdown');



throw new Exception('The my exception', 123);
