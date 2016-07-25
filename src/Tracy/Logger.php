<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;


/**
 * Logger.
 */
class Logger implements ILogger
{
	/** @var string name of the directory where errors should be logged */
	public $directory;

	/** @var string|array email or emails to which send error notifications */
	public $email;

	/** @var string sender of email notifications */
	public $fromEmail;

	/** @var mixed interval for sending email is 2 days */
	public $emailSnooze = '2 days';

	/** @var BlueScreen */
	private $blueScreen;

	/** @var MessageFormat */
	private $messageFormat;

	/** @var array of INotifier instances */
	private $notifiers;


	public function __construct($directory, $email = NULL, BlueScreen $blueScreen = NULL)
	{
		$this->directory = $directory;
		$this->blueScreen = $blueScreen;
		$this->messageFormat = new MessageFormat();

		$mailNotifier = new MailNotifier($email, $directory);

		$this->notifiers = [$mailNotifier];
	}


	/**
	 * Logs message or exception to file and sends email notification.
	 * @param  string|\Exception|\Throwable
	 * @param  int   one of constant ILogger::INFO, WARNING, ERROR (sends email), EXCEPTION (sends email), CRITICAL (sends email)
	 * @return string logged error filename
	 */
	public function log($message, $priority = self::INFO)
	{
		if (!$this->directory) {
			throw new \LogicException('Directory is not specified.');
		} elseif (!is_dir($this->directory)) {
			throw new \RuntimeException("Directory '$this->directory' is not found or is not directory.");
		}

		$exceptionFile = $message instanceof \Exception || $message instanceof \Throwable
			? $this->getExceptionFile($message)
			: NULL;
		$line = $this->messageFormat->formatLogLine($message, $exceptionFile);
		$file = $this->directory . '/' . strtolower($priority ?: self::INFO) . '.log';

		if (!@file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX)) { // @ is escalated to exception
			throw new \RuntimeException("Unable to write to log file '$file'. Is directory writable?");
		}

		if ($exceptionFile) {
			$this->logException($message, $exceptionFile);
		}

		foreach ($this->notifiers as $notifier) {
			$notifier->notify($message, $priority);
		}

		return $exceptionFile;
	}


	/**
	 * @param  \Exception|\Throwable
	 * @return string
	 */
	public function getExceptionFile($exception)
	{
		$dir = strtr($this->directory . '/', '\\/', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
		$hash = substr(md5(preg_replace('~(Resource id #)\d+~', '$1', $exception)), 0, 10);
		foreach (new \DirectoryIterator($this->directory) as $file) {
			if (strpos($file, $hash)) {
				return $dir . $file;
			}
		}
		return $dir . 'exception--' . @date('Y-m-d--H-i') . "--$hash.html"; // @ timezone may not be set
	}


	/**
	 * @param  \Tracy\INotifier $notifier
	 * @return void
	 */
	public function addNotifier(INotifier $notifier)
	{
		$this->notifiers[] = $notifier;
	}


	/**
	 * Logs exception to the file if file doesn't exist.
	 * @param  \Exception|\Throwable
	 * @return string logged error filename
	 */
	protected function logException($exception, $file = NULL)
	{
		$file = $file ?: $this->getExceptionFile($exception);
		$bs = $this->blueScreen ?: new BlueScreen;
		$bs->renderToFile($exception, $file);
		return $file;
	}

}
