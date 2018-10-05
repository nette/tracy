<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bridges\Psr;

use Psr;
use Tracy;


/**
 * Tracy\ILogger to Psr\Log\LoggerInterface adapter.
 */
class TracyToPsrLoggerAdapter extends Psr\Log\AbstractLogger
{
	/** PSR-3 log level to Tracy logger priority mapping */
	private const PRIORITY_MAP = [
		Psr\Log\LogLevel::EMERGENCY => Tracy\ILogger::CRITICAL,
		Psr\Log\LogLevel::ALERT => Tracy\ILogger::CRITICAL,
		Psr\Log\LogLevel::CRITICAL => Tracy\ILogger::CRITICAL,
		Psr\Log\LogLevel::ERROR => Tracy\ILogger::ERROR,
		Psr\Log\LogLevel::WARNING => Tracy\ILogger::WARNING,
		Psr\Log\LogLevel::NOTICE => Tracy\ILogger::WARNING,
		Psr\Log\LogLevel::INFO => Tracy\ILogger::INFO,
		Psr\Log\LogLevel::DEBUG => Tracy\ILogger::DEBUG,
	];

	/** @var Tracy\ILogger */
	private $tracyLogger;


	public function __construct(Tracy\ILogger $tracyLogger)
	{
		$this->tracyLogger = $tracyLogger;
	}


	public function log($level, $message, array $context = [])
	{
		$priority = self::PRIORITY_MAP[$level] ?? Tracy\ILogger::ERROR;

		if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
			$this->tracyLogger->log($context['exception'], $priority);
			unset($context['exception']);
		}

		if ($context) {
			$message = [
				'message' => $message,
				'context' => $context,
			];
		}

		$this->tracyLogger->log($message, $priority);
	}
}
