<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Tracy\Debugger::$customJsFiles[] = __DIR__ . '/fixtures/custom.asset';

$handler = Tracy\Debugger::getStrategy();
ob_start();
$_GET['_tracy_bar'] = 'js';
$handler->sendAssets();
$output = ob_get_clean();

Assert::contains('custom-asset {}', $output);
