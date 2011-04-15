<?php

/**
 * Test: Nette\Diagnostics\Debugger notices and warnings in production mode.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = FALSE;
Debugger::$productionMode = TRUE;

Debugger::enable();

function shutdown() {
	Assert::same('', ob_get_clean());
}
ob_start();
Debugger::$onFatalError[] = 'shutdown';


mktime(); // E_STRICT
mktime(0, 0, 0, 1, 23, 1978, 1); // E_DEPRECATED
$x++; // E_NOTICE
rename('..', '..'); // E_WARNING
require 'E_COMPILE_WARNING.inc'; // E_COMPILE_WARNING
