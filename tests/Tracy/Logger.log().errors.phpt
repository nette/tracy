<?php

/**
 * Test: Tracy\Logger::log() error.
 */

use Tracy\Logger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$logger = new Logger;
$logger->directory = TEMP_DIR;
$logger->log('Hello'); // no error


Assert::exception(function() {
	$logger = new Logger;
	$logger->directory = TEMP_DIR . '/unknown';
	$logger->log('Hello');
}, 'RuntimeException', "Directory '%a%' is not found or is not directory.");


Assert::exception(function() {
	$logger = new Logger;
	$logger->directory = TEMP_DIR;
	mkdir(TEMP_DIR . '/test.log');
	$logger->log('Hello', 'test');
}, 'RuntimeException', "Unable to write to log file '%a%'. Is directory writable?");
