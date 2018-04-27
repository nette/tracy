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


	/**
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->path = $path;
	}


	public function log($message, $priority = self::INFO)
	{
		$line = $this->formatLogLine($message);
		if (!@file_put_contents($this->path, $line . PHP_EOL, FILE_APPEND | LOCK_EX)) { // @ is escalated to exception
			throw new \RuntimeException("Unable to write to log file '{$this->path}'. Is directory writable?");
		}
	}


	/**
	 * @param  string|\Exception|\Throwable
	 * @return string
	 */
	protected function formatLogLine($message)
	{
		return implode(' ', [
			@date('[Y-m-d H-i-s]'), // @ timezone may not be set
			preg_replace('#\s*\r?\n\s*#', ' ', $this->formatMessage($message)),
			' @  ' . Helpers::getSource(),
		]);
	}


	/**
	 * @param  string|\Exception|\Throwable
	 * @return string
	 */
	protected function formatMessage($message)
	{
		if ($message instanceof \Exception || $message instanceof \Throwable) {
			while ($message) {
				$tmp[] = ($message instanceof \ErrorException
						? Helpers::errorTypeToString($message->getSeverity()) . ': ' . $message->getMessage()
						: Helpers::getClass($message) . ': ' . $message->getMessage() . ($message->getCode() ? ' #' . $message->getCode() : '')
					) . ' in ' . $message->getFile() . ':' . $message->getLine();
				$message = $message->getPrevious();
			}
			$message = implode("\ncaused by ", $tmp);

		} elseif (!is_string($message)) {
			$message = Dumper::toText($message);
		}

		return trim($message);
	}
}
