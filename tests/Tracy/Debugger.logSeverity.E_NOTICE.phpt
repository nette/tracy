<?php

/**
 * Test: Tracy\Debugger logging E_NOTICE (bluescreen) in production mode.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


// Setup environment
Debugger::enable(Debugger::PRODUCTION, getTempDir());
Debugger::$logSeverity = E_NOTICE;

$variable = $missingVariable;

Assert::count(1, glob(getTempDir() . '/exception*.html'));
Assert::count(1, glob(getTempDir() . '/error.log'));
