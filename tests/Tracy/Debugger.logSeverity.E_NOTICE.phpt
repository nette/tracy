<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger logging E_NOTICE (bluescreen) in production mode.
 */

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


// Setup environment
Tester\Helpers::purge(getTempDir());
Debugger::enable(Debugger::Production, getTempDir());
Debugger::$logSeverity = E_NOTICE;

$variable = &pi();

Assert::same('Only variables should be assigned by reference', error_get_last()['message']);

Assert::count(1, glob(getTempDir() . '/error*.html'));
Assert::count(1, glob(getTempDir() . '/error.log'));
