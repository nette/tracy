<?php

/**
 * Test: Tracy\Debugger E_ERROR in console.
 *
 * @author     David Grudl
 * @package    Tracy
 * @subpackage UnitTests
 */

use Tracy\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = TRUE;
Debugger::$productionMode = FALSE;

Debugger::$blueScreen->collapsePaths[] = __DIR__;

Assert::true(Debugger::$blueScreen->isCollapsed(__FILE__));
Assert::false(Debugger::$blueScreen->isCollapsed(dirname(__DIR__) . 'somethingElse'));
