<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Tracy\Debugger::$customCssFiles[] = __DIR__ . '/fixtures/custom.asset';

$blueScreen = new Tracy\BlueScreen;
ob_start();
$blueScreen->render(new Exception);
$output = ob_get_clean();

// divided into two strings so that the searched string is not found in the source code of this file
Assert::contains('custom-asset{}', $output);


$handler = Tracy\Debugger::getStrategy();
ob_start();
$_GET['_tracy_bar'] = 'js';
$handler->sendAssets();
$output = ob_get_clean();

Assert::contains('custom-asset{}', $output);
