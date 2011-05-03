<?php

/**
 * Test: Nette\Diagnostics\Debugger::dump() with $showLocation.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = FALSE;
Debugger::$productionMode = FALSE;



Debugger::$showLocation = TRUE;

ob_start();
Debugger::dump('xxx');
Assert::match( '<pre class="nette-dump">"xxx" (3) <small>in %a%:%d%</small>
</pre>', ob_get_clean() );
