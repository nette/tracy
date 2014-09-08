<?php

/**
 * Test: Tracy\Logger it can be extended.
 */

use Tracy\Logger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';



class CustomLogger extends Logger
{

	public $collector = array();

	public function log($value, $priority = self::INFO)
	{
		$exceptionFile = $value instanceof \Exception ? $this->logException($value) : NULL;
		$message = $this->formatMessage($value);

		$this->collector[] = array($priority, $message, $exceptionFile);

		return $exceptionFile;
	}

}



test(function() {
	$logger = new CustomLogger(TEMP_DIR);
	$logger->log(new Exception('First'), 'a');

	Assert::match('a', $logger->collector[0][0]);
	Assert::match('[%a%] Exception: First in %a%:%d% %A%', $logger->collector[0][1]);
	Assert::match('%a%%ds%exception-%a%.html', $logger->collector[0][2]);
});

test(function() {
	$logger = new CustomLogger(TEMP_DIR);
	$logger->log('message', 'b');

	Assert::match('b', $logger->collector[0][0]);
	Assert::match('[%a%] message %A%', $logger->collector[0][1]);
	Assert::null($logger->collector[0][2]);
});
