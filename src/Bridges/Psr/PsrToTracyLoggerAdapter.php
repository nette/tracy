<?php declare(strict_types=1);

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy\Bridges\Psr;

use Psr;
use Tracy;
use function is_string;


/**
 * Psr\Log\LoggerInterface to Tracy\ILogger adapter.
 */
class PsrToTracyLoggerAdapter implements Tracy\ILogger
{
	/** Tracy logger level to PSR-3 log level mapping */
	private const LevelMap = [
		Tracy\ILogger::DEBUG => Psr\Log\LogLevel::DEBUG,
		Tracy\ILogger::INFO => Psr\Log\LogLevel::INFO,
		Tracy\ILogger::WARNING => Psr\Log\LogLevel::WARNING,
		Tracy\ILogger::ERROR => Psr\Log\LogLevel::ERROR,
		Tracy\ILogger::EXCEPTION => Psr\Log\LogLevel::ERROR,
		Tracy\ILogger::CRITICAL => Psr\Log\LogLevel::CRITICAL,
	];


	public function __construct(
		private readonly Psr\Log\LoggerInterface $psrLogger,
	) {
	}


	public function log(mixed $value, string $level = self::INFO): void
	{
		if ($value instanceof \Throwable) {
			$message = get_debug_type($value) . ': ' . $value->getMessage() . ($value->getCode() ? ' #' . $value->getCode() : '') . ' in ' . $value->getFile() . ':' . $value->getLine();
			$context = ['exception' => $value];

		} elseif (!is_string($value)) {
			$message = trim(Tracy\Dumper::toText($value));
			$context = [];

		} else {
			$message = $value;
			$context = [];
		}

		$this->psrLogger->log(
			self::LevelMap[$level] ?? Psr\Log\LogLevel::ERROR,
			$message,
			$context,
		);
	}
}
