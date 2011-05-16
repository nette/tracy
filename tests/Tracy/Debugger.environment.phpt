<?php

/**
 * Test: Nette\Diagnostics\Debugger and Environment.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger,
	Nette\Environment;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = FALSE;



Assert::null( Debugger::$productionMode );

// setting production environment...

Environment::setProductionMode();
Debugger::enable();

Assert::true( Debugger::$productionMode );
