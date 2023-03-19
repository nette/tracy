<?php

/**
 * Test: Tracy\StreamLogger logging exceptions in log message.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\MultiLogger;
use Tracy\StreamLogger;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$e = new Exception('First');

	$path1 = getTempDir() . '/a1.log';
	$logger1 = new StreamLogger($path1);
	$path2 = getTempDir() . '/a2.log';
	$logger2 = new StreamLogger($path2);

	$logger = new MultiLogger([$logger1, $logger2]);
	$logger->log($e, 'a');

	Assert::match('[%a%] Exception: First in %a%:%d% %A%', file_get_contents($path1));
	Assert::match('[%a%] Exception: First in %a%:%d% %A%', file_get_contents($path2));
});
