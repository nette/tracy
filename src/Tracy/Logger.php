<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


/**
 * Logger.
 */
class Logger implements ILogger
{
	/** @var string|null name of the directory where errors should be logged */
	public $directory;

	/** @var string|array|null email or emails to which send error notifications */
	public $email;

	/** @var string|null sender of email notifications */
	public $fromEmail;

	/** @var mixed interval for sending email is 2 days */
	public $emailSnooze = '2 days';

	/** @var callable handler for sending emails */
	public $mailer;

	/** @var BlueScreen|null */
	private $blueScreen;

	/** @var StreamLogger[] */
	private $streamLoggers;

	/** @var BlueScreenLogger */
	private $blueScreenLogger;

	/** @var MailLogger */
	private $mailLogger;


	/**
	 * @param  string|array|null  $email
	 */
	public function __construct(?string $directory, $email = null, BlueScreen $blueScreen = null)
	{
		$this->directory = $directory;
		$this->email = $email;
		$this->blueScreen = $blueScreen;
		$this->mailer = [$this, 'defaultMailer'];

		$this->blueScreenLogger = $this->createBlueScreenLogger();
		$this->mailLogger = $this->createMailLogger();
	}


	/**
	 * Logs message or exception to file and sends email notification.
	 * @param  mixed  $message
	 * @param  string  $priority  one of constant ILogger::INFO, WARNING, ERROR (sends email), EXCEPTION (sends email), CRITICAL (sends email)
	 * @return string|null logged error filename
	 */
	public function log($message, string $priority = self::INFO): ?string
	{
		$exceptionFile = $this->getStreamLogger($priority)->log($message, $priority);
		$this->mailLogger->log($message, $priority);
		return $exceptionFile;
	}


	protected function getStreamLogger($priority)
	{
		if (!$this->directory) {
			throw new \LogicException('Logging directory is not specified.');
		} elseif (!is_dir($this->directory)) {
			throw new \RuntimeException("Logging directory '$this->directory' is not found or is not directory.");
		}

		$path = $this->directory . '/' . strtolower($priority ?: self::INFO) . '.log';
		if (!isset($this->streamLoggers[$path])) {
			$this->streamLoggers[$path] = new StreamLogger($path, $this->blueScreenLogger);
		}

		return $this->streamLoggers[$path];
	}


	protected function createBlueScreenLogger()
	{
		$blueScreenLogger = new BlueScreenLogger($this->directory, $this->blueScreen);
		$blueScreenLogger->directory = &$this->directory;

		return $blueScreenLogger;
	}


	protected function createMailLogger()
	{
		$mailLogger = new MailLogger($this->directory, $this->email);
		$mailLogger->directory = &$this->directory;
		$mailLogger->email = &$this->email;
		$mailLogger->fromEmail = &$this->fromEmail;
		$mailLogger->emailSnooze = &$this->emailSnooze;
		$mailLogger->mailer = &$this->mailer;

		return $mailLogger;
	}


	/**
	 * @param  mixed  $message
	 */
	public static function formatMessage($message): string
	{
		if ($message instanceof \Throwable) {
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


	/**
	 * @param  mixed  $message
	 */
	public static function formatLogLine($message, string $exceptionFile = null): string
	{
		return implode(' ', [
			@date('[Y-m-d H-i-s]'), // @ timezone may not be set
			preg_replace('#\s*\r?\n\s*#', ' ', static::formatMessage($message)),
			' @  ' . Helpers::getSource(),
			$exceptionFile ? ' @@  ' . basename($exceptionFile) : null,
		]);
	}


	/**
	 * @deprecated
	 */
	public function getExceptionFile(\Throwable $exception): string
	{
		return $this->blueScreenLogger->getExceptionFile($exception);
	}


	/**
	 * @deprecated
	 */
	protected function logException(\Throwable $exception, ?string $file = null): string
	{
		$reflection = new \ReflectionMethod($this->blueScreenLogger, 'logException');
		$reflection->setAccessible(true);
		return $reflection->invoke($this->blueScreenLogger, $exception, $file);
	}


	/**
	 * @deprecated
	 */
	protected function sendEmail($message): void
	{
		$reflection = new \ReflectionMethod($this->mailLogger, 'sendEmail');
		$reflection->setAccessible(true);
		$reflection->invoke($this->mailLogger, $message);
	}


	/**
	 * @deprecated
	 * @internal
	 */
	public function defaultMailer($message, string $email): void
	{
		$this->mailLogger->defaultMailer($message, $email);
	}
}
