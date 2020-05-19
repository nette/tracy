<?php

/**
 * Test: Tracy\Logger::log() error.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Logger;


require __DIR__ . '/../bootstrap.php';


$logger = new Logger(getTempDir());
$logger->log('Hello'); // no error


Assert::exception(function () {
	$logger = new Logger(getTempDir() . '/unknown');
	$logger->log('Hello');
}, RuntimeException::class, "Logging directory '%a%' is not found or is not directory.");


Assert::exception(function () {
	$logger = new Logger(getTempDir());
	mkdir(getTempDir() . '/test.log');
	$logger->log('Hello', 'test');
}, RuntimeException::class, "Unable to write to log file '%a%'. Is directory writable?");
