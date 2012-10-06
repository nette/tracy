<?php

/**
 * Test: Nette\Diagnostics\Debugger::dump() and locale.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleColors = NULL;
Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');
setLocale(LC_ALL, 'czech');



Assert::match( 'array(2) [
   0 => -10.0
   1 => 10.3
]

', Debugger::dump(array(-10.0, 10.3), TRUE));
