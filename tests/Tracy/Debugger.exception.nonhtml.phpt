<?php

/**
 * Test: Nette\Diagnostics\Debugger exception in non-HTML mode.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = FALSE;
Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();

function shutdown() {
	Assert::same('', ob_get_clean());
}
Assert::handler('shutdown');



throw new Exception('The my exception', 123);
