<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;


/**
 * Logger which writes to a file.
 */
class StreamLogger implements ILogger
{
	/** @var string path to a file where errors should be logged */
	public $path;

	/** @var BlueScreenLogger|null */
	private $blueScreenLogger;


	/**
	 * @param string $path
	 */
	public function __construct($path, ?BlueScreenLogger $blueScreenLogger = null)
	{
		$this->path = $path;
		$this->blueScreenLogger = $blueScreenLogger;
	}


	public function log($message, string $priority = self::INFO): ?string
	{
		$exceptionFile = $this->blueScreenLogger ? $this->blueScreenLogger->log($message, $priority) : null;
		$line = Logger::formatLogLine($message, $exceptionFile);
		if (!@file_put_contents($this->path, $line . PHP_EOL, FILE_APPEND | LOCK_EX)) { // @ is escalated to exception
			throw new \RuntimeException("Unable to write to log file '{$this->path}'. Is directory writable?");
		}

		return $exceptionFile;
	}
}
