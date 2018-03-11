<?php

/**
 * Test: Tracy\Debugger logging E_NOTICE (bluescreen) in development mode.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


// Setup environment
Debugger::enable(Debugger::DEVELOPMENT, TEMP_DIR);
Debugger::$logSeverity = E_NOTICE;

$variable = $missingVariable;

Assert::count(0, glob(TEMP_DIR . '/exception*.html'));
Assert::count(0, glob(TEMP_DIR . '/error.log'));
