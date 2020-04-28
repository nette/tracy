<?php

declare(strict_types=1);

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Tracy\Debugger::$customJsFiles[] = __DIR__ . '/fixtures/custom.asset';

$bar = new Tracy\Bar;
ob_start();
$_GET['_tracy_bar'] = 'js';
$bar->dispatchAssets();
$output = ob_get_clean();

Assert::contains('custom-asset {}', $output);
