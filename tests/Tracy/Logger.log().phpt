<?php

/**
 * Test: Tracy\Logger logging exceptions in log message.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Logger;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$e = new Exception('First');
	$logger = new Logger(getTempDir());
	$logger->log($e, 'a');
	Assert::match('[%a%] Exception: First in %a%:%d% %A%', file_get_contents($logger->directory . '/a.log'));
});

test('', function () {
	$e = new Exception('First');
	$e = new InvalidArgumentException('Second', 0, $e);
	$e = new RuntimeException('Third', 0, $e);
	$logger = new Logger(getTempDir());
	$logger->log($e, 'b');
	Assert::match('[%a%] RuntimeException: Third in %a%:%d% caused by InvalidArgumentException: Second in %a%:%d% caused by Exception: First in %a%:%d% %A%', file_get_contents($logger->directory . '/b.log'));
});

test('', function () {
	$logger = new Logger(getTempDir());
	$logger->log(new ErrorException('Msg', 0, E_ERROR, __FILE__, __LINE__), 'c');
	Assert::match('[%a%] Fatal Error: Msg in %a%Logger.log().phpt:%d%  @  %a%  @@  c-%a%.html', file_get_contents($logger->directory . '/c.log'));
});

test('', function () {
	$logger = new Logger(getTempDir());
	$logger->log(new ErrorException('Msg', 0, E_WARNING, __FILE__, __LINE__), 'd');
	Assert::match('[%a%] Warning: Msg in %a%Logger.log().phpt:%d%  @  %a%  @@  d-%a%.html', file_get_contents($logger->directory . '/d.log'));
});

test('', function () {
	$logger = new Logger(getTempDir());
	$logger->log(new ErrorException('Msg', 0, E_COMPILE_ERROR, __FILE__, __LINE__), 'e');
	Assert::match('[%a%] Compile Error: Msg in %a%Logger.log().phpt:%d%  @  %a%  @@  e-%a%.html', file_get_contents($logger->directory . '/e.log'));
});

test('', function () {
	$logger = new Logger(getTempDir());
	$logger->log(new ErrorException('Msg', 0, E_NOTICE, __FILE__, __LINE__), 'f');
	Assert::match('[%a%] Notice: Msg in %a%Logger.log().phpt:%d%  @  %a%  @@  f-%a%.html', file_get_contents($logger->directory . '/f.log'));
});
