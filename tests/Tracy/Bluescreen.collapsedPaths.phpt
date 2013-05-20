<?php

/**
 * Test: Tracy\Debugger E_ERROR in console.
 *
 * @author     David Grudl
 * @package    Tracy
 * @subpackage UnitTests
 */

require __DIR__ . '/../bootstrap.php';



$blueScreen = new Tracy\BlueScreen;

$blueScreen->collapsePaths[] = __DIR__;

Assert::true($blueScreen->isCollapsed(__FILE__));
Assert::false($blueScreen->isCollapsed(dirname(__DIR__) . 'somethingElse'));
