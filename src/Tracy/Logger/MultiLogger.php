<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


class MultiLogger implements ILogger
{

	/** @var ILogger[] */
	private array $loggers = [];


	/**
	 * @param ILogger[] $loggers
	 */
	public function __construct(array $loggers = [])
	{
		$this->loggers = $loggers;
	}


	public function addLogger(ILogger $logger): void
	{
		$this->loggers[] = $logger;
	}


	public function log(mixed $message, string $level = self::INFO)
	{
		foreach ($this->loggers as $logger) {
			$logger->log($message, $level);
		}
	}

}
