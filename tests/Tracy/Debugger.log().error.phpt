<?php

/**
 * Test: Tracy\Debugger logging error.
 */

use Tracy\Debugger;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::exception(function () {
	Debugger::log('Hello');
}, 'LogicException', 'Directory is not specified.');


// no error
Debugger::$logDirectory = TEMP_DIR;
Debugger::log('Hello');


Debugger::$logDirectory = TEMP_DIR . '/unknown';
Assert::exception(function () {
	Debugger::log('Hello');
}, 'RuntimeException', "Directory '%a%' is not found or is not directory.");
