<?php

/**
 * Test: Tracy\Debugger logging E_NOTICE (bluescreen) in production mode.
 */

use Tracy\Debugger,
  Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// Setup environment
Debugger::enable(Debugger::PRODUCTION, TEMP_DIR);
Debugger::$logSeverity = E_NOTICE;

$variable = $missingVariable;

Assert::same(1, count(glob(TEMP_DIR . '/exception*.html')));
Assert::same(1, count(glob(TEMP_DIR . '/error.log')));
