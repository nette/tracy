<?php

/**
 * Test: Tracy\Logger it can be extended.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Logger;


require __DIR__ . '/../bootstrap.php';



class CustomLogger extends Logger
{
	public $collector = [];


	public function log($value, string $priority = self::INFO): ?string
	{
		$exceptionFile = $value instanceof \Exception ? $this->logException($value) : null;

		$this->collector[] = [
			$priority,
			$this->formatMessage($value),
			$this->formatLogLine($value, $exceptionFile),
			$exceptionFile,
		];

		return $exceptionFile;
	}
}



test(function () {
	$logger = new CustomLogger(TEMP_DIR);
	$logger->log(new Exception('First'), 'a');

	Assert::match('a', $logger->collector[0][0]);
	Assert::match('Exception: First in %a%:%d%', $logger->collector[0][1]);
	Assert::match('[%a%] Exception: First in %a%:%d%  @  CLI (PID: %d%): %a%  @@  exception-%a%.html', $logger->collector[0][2]);
	Assert::match('%a%%ds%exception-%a%.html', $logger->collector[0][3]);
});

test(function () {
	$logger = new CustomLogger(TEMP_DIR);
	$logger->log('message', 'b');

	Assert::match('b', $logger->collector[0][0]);
	Assert::match('message', $logger->collector[0][1]);
	Assert::match('[%a%] message  @  CLI (PID: %d%): %a%', $logger->collector[0][2]);
	Assert::null($logger->collector[0][3]);
});
