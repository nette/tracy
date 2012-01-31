<?php

/**
 * Test: Nette\Diagnostics\Debugger E_ERROR in console.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = TRUE;
Debugger::$productionMode = FALSE;

Debugger::$blueScreen->collapsePaths[] = __DIR__;

Assert::true(Debugger::$blueScreen->isCollapsed(__FILE__));
Assert::false(Debugger::$blueScreen->isCollapsed(dirname(__DIR__) . 'somethingElse'));
