<?php

/**
 * Test: Tracy\StreamLogger logging exceptions in log message.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\StreamLogger;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$path = getTempDir() . '/a.log';
	$e = new Exception('First');
	$logger = new StreamLogger($path);
	$logger->log($e, 'a');
	Assert::match('[%a%] Exception: First in %a%:%d% %A%', file_get_contents($path));
});
