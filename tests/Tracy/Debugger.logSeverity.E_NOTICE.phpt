<?php

/**
 * Test: Tracy\Debugger logging E_NOTICE (bluescreen) in production mode.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


// Setup environment
Debugger::enable(Debugger::PRODUCTION, TEMP_DIR);
Debugger::$logSeverity = E_NOTICE;

$variable = $missingVariable;

Assert::count(1, glob(TEMP_DIR . '/exception*.html'));
Assert::count(1, glob(TEMP_DIR . '/error.log'));
