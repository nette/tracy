<?php

/**
 * Test: Tracy\Debugger logging error.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Assert::exception(
	fn() => Debugger::log('Hello'),
	LogicException::class,
	'Logging directory is not specified.',
);


// no error
Debugger::$logDirectory = getTempDir();
Debugger::log('Hello');


Debugger::$logDirectory = getTempDir() . '/unknown';
Assert::exception(
	fn() => Debugger::log('Hello'),
	RuntimeException::class,
	"Logging directory '%a%' is not found or is not directory.",
);
