<?php

/**
 * Test: Tracy\StreamLogger logging exceptions in log message.
 * @outputMatch %A%Exception: First%A%
 */

declare(strict_types=1);

use Tracy\StreamLogger;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$e = new Exception('First');
	$logger = new StreamLogger('php://stdout');
	$logger->log($e);
});
