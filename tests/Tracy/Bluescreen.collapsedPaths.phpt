<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$blueScreen = new Tracy\BlueScreen;

$blueScreen->collapsePaths[] = __DIR__;
$blueScreen->collapsePaths[] = dirname(__DIR__) . '/bootstrap.php';

Assert::true($blueScreen->isCollapsed(__FILE__));
Assert::true($blueScreen->isCollapsed(dirname(__DIR__) . '/bootstrap.php'));
Assert::false($blueScreen->isCollapsed(dirname(__DIR__) . '/bootstrap.php.tmp'));
Assert::false($blueScreen->isCollapsed(dirname(__DIR__) . 'somethingElse'));
Assert::false($blueScreen->isCollapsed(__DIR__ . '-dir'));
