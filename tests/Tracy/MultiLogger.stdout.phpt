<?php

/**
 * Test: Tracy\StreamLogger logging exceptions in log message.
 * @outputMatch %A%Exception: First%A%
 */

declare(strict_types=1);

use Tracy\MultiLogger;
use Tracy\StreamLogger;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$e = new Exception('First');
	$logger1 = new StreamLogger('php://stdout');
	$logger = new MultiLogger([$logger1]);
	$logger->log($e);
});
