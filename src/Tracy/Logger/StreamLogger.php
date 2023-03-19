<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


class StreamLogger implements ILogger
{

	/** @var string path to a file where errors should be logged */
	public string $path;


	public function __construct(string $path)
	{
		$this->path = $path;
	}


	public function log(mixed $message, string $level = self::INFO)
	{
		$line = $this->formatLogLine($message);

		if (!@file_put_contents($this->path, $line . PHP_EOL)) { // @ is escalated to exception
			throw new \RuntimeException("Unable to write to log file '{$this->path}'. Is directory writable?");
		}
	}


	public static function formatMessage(mixed $message): string
	{
		if ($message instanceof \Throwable) {
			foreach (Helpers::getExceptionChain($message) as $exception) {
				$tmp[] = ($exception instanceof \ErrorException
						? Helpers::errorTypeToString($exception->getSeverity()) . ': ' . $exception->getMessage()
						: get_debug_type($exception) . ': ' . $exception->getMessage() . ($exception->getCode() ? ' #' . $exception->getCode() : '')
					) . ' in ' . $exception->getFile() . ':' . $exception->getLine();
			}

			$message = implode("\ncaused by ", $tmp);

		} elseif (!is_string($message)) {
			$message = Dumper::toText($message);
		}

		return trim($message);
	}


	public static function formatLogLine(mixed $message): string
	{
		return implode(' ', [
			date('[Y-m-d H-i-s]'),
			preg_replace('#\s*\r?\n\s*#', ' ', static::formatMessage($message)),
			' @  ' . Helpers::getSource()
		]);
	}


}
