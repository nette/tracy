<?php

/**
 * Test: Tracy\Logger send exceptions to logger handlers.
 */

use Tracy\ILoggerHandler;
use Tracy\Logger;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class LoggerHandler implements ILoggerHandler
{
	public $message;
	public $priority;

	public function __invoke($message, $priority)
	{
		$this->message = $message;
		$this->priority = $priority;
	}
}

$loggerHandler = new LoggerHandler();
$logger = new Logger(TEMP_DIR);
$logger->addHandler($loggerHandler);

$e = new Exception('Same error.');
$logger->log($e, 'a');
Assert::equal($e, $loggerHandler->message);
Assert::equal('a', $loggerHandler->priority);

$logger->log('Same error.', 'b');
Assert::equal('Same error.', $loggerHandler->message);
Assert::equal('b', $loggerHandler->priority);
