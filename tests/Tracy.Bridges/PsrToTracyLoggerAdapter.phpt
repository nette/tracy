<?php

/**
 * Test: PsrToTracyLoggerAdapter.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Bridges\Psr\PsrToTracyLoggerAdapter;
use Tracy\ILogger;


require __DIR__ . '/../bootstrap.php';

class DummyPsrLogger extends Psr\Log\AbstractLogger
{
	/** @var array */
	public $entries = [];


	public function log($level, $message, array $context = [])
	{
		$this->entries[] = [$level, $message, $context];
	}
}


$psrLogger = new DummyPsrLogger;
$tracyLogger = new PsrToTracyLoggerAdapter($psrLogger);
$exception = new \Exception('Something went wrong');

$tracyLogger->log('info');
$tracyLogger->log('warning', ILogger::WARNING);
$tracyLogger->log(123);
$tracyLogger->log(['x' => 'y']);
$tracyLogger->log($exception);

Assert::same([
	[Psr\Log\LogLevel::INFO, 'info', []],
	[Psr\Log\LogLevel::WARNING, 'warning', []],
	[Psr\Log\LogLevel::INFO, '123', []],
	[Psr\Log\LogLevel::INFO, "array (1)\n   x => \"y\"", []],
	[Psr\Log\LogLevel::INFO, 'Something went wrong', ['exception' => $exception]],
], $psrLogger->entries);
