<?php

/**
 * Test: Tracy\Logger logging exceptions in log message.
 */

use Tracy\Logger;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$e = new Exception('First');
	$logger = new Logger(TEMP_DIR);
	$logger->log($e, 'a');
	Assert::match('[%a%] Exception: First in %a%:%d% %A%', file_get_contents($logger->directory . '/a.log'));
});

test(function () {
	$e = new Exception('First');
	$e = new InvalidArgumentException('Second', 0, $e);
	$e = new RuntimeException('Third', 0, $e);
	$logger = new Logger(TEMP_DIR);
	$logger->log($e, 'b');
	Assert::match('[%a%] RuntimeException: Third in %a%:%d% caused by InvalidArgumentException: Second in %a%:%d% caused by Exception: First in %a%:%d% %A%', file_get_contents($logger->directory . '/b.log'));
});
