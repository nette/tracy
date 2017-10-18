<?php

/**
 * Test: Tracy\Debugger logging error.
 */

use Tester\Assert;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Assert::exception(function () {
	Debugger::log('Hello');
}, 'LogicException', 'Logging directory is not specified.');


// no error
Debugger::$logDirectory = TEMP_DIR;
Debugger::log('Hello');


Debugger::$logDirectory = TEMP_DIR . '/unknown';
Assert::exception(function () {
	Debugger::log('Hello');
}, 'RuntimeException', "Logging directory '%a%' is not found or is not directory.");
