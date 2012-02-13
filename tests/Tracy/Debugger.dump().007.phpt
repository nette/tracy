<?php

/**
 * Test: Nette\Diagnostics\Debugger::dump() and locale.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleColors = NULL;
Debugger::$consoleMode = TRUE;
Debugger::$productionMode = FALSE;
setLocale(LC_ALL, 'czech');



Assert::match( 'array(2) [
   0 => -10.0
   1 => 10.3
]

', Debugger::dump(array(-10.0, 10.3), TRUE));
