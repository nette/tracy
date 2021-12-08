<?php

/**
 * Test: TracyToPsrLoggerAdapter.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Bridges\Psr\TracyToPsrLoggerAdapter;
use Tracy\ILogger;


require __DIR__ . '/../bootstrap.php';

class DummyTracyLogger implements ILogger
{
	/** @var array */
	public $entries = [];


	public function log($value, $priority = self::INFO)
	{
		$this->entries[] = [$priority, $value];
	}
}


$tracyLogger = new DummyTracyLogger;
$psrLogger = new TracyToPsrLoggerAdapter($tracyLogger);
$exception = new Exception('Something went wrong');

$psrLogger->info('info');
$psrLogger->warning('warning');
$psrLogger->error('order failed with exception', ['exception' => $exception]);
$psrLogger->error('order failed with context', ['orderId' => 123]);
$psrLogger->error('order failed with context and exception', ['orderId' => 123, 'exception' => $exception]);

Assert::same([
	[ILogger::INFO, 'info'],
	[ILogger::WARNING, 'warning'],
	[ILogger::ERROR, $exception],
	[ILogger::ERROR, 'order failed with exception'],
	[ILogger::ERROR, ['message' => 'order failed with context', 'context' => ['orderId' => 123]]],
	[ILogger::ERROR, $exception],
	[ILogger::ERROR, ['message' => 'order failed with context and exception', 'context' => ['orderId' => 123]]],
], $tracyLogger->entries);
