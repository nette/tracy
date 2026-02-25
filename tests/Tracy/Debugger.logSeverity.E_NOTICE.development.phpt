<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger logging E_NOTICE (bluescreen) in development mode.
 */

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


// Setup environment
Debugger::enable(Debugger::Development, getTempDir());
Debugger::$logSeverity = E_NOTICE;

$variable = &pi();

Assert::count(0, glob(getTempDir() . '/exception*.html'));
Assert::count(0, glob(getTempDir() . '/error.log'));
