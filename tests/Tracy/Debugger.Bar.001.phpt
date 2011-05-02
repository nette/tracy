<?php

/**
 * Test: Nette\Diagnostics\Debugger Bar in HTML.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = FALSE;
Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

function shutdown() {
	Assert::match('%A%<!-- Nette Debug Bar -->%A%', ob_get_clean());
}
ob_start();
Debugger::$onFatalError[] = 'shutdown';
