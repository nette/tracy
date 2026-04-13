<?php declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


$blueScreen = new Tracy\BlueScreen;

$original = Debugger::$transparentPaths;
Debugger::$transparentPaths[] = __DIR__;
Debugger::$transparentPaths[] = dirname(__DIR__) . '/bootstrap.php';

try {
	Assert::true($blueScreen->isCollapsed(__FILE__));
	Assert::true($blueScreen->isCollapsed(dirname(__DIR__) . '/bootstrap.php'));
} finally {
	Debugger::$transparentPaths = $original;
}
