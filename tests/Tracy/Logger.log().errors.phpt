<?php

/**
 * Test: Tracy\Logger::log() error.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Logger;


require __DIR__ . '/../bootstrap.php';


$logger = new Logger(TEMP_DIR);
$logger->log('Hello'); // no error


Assert::exception(function () {
	$logger = new Logger(TEMP_DIR . '/unknown');
	$logger->log('Hello');
}, 'RuntimeException', "Logging directory '%a%' is not found or is not directory.");


Assert::exception(function () {
	$logger = new Logger(TEMP_DIR);
	mkdir(TEMP_DIR . '/test.log');
	$logger->log('Hello', 'test');
}, 'RuntimeException', "Unable to write to log file '%a%'. Is directory writable?");
