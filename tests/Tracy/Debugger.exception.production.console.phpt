<?php

/**
 * Test: Nette\Diagnostics\Debugger exception in production & console mode.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = TRUE;
Debugger::$productionMode = TRUE;

Debugger::enable();

function shutdown() {
	Assert::match('ERROR:%A%', ob_get_clean());
	die(0);
}
ob_start();
Debugger::$onFatalError[] = 'shutdown';


throw new Exception('The my exception', 123);
