<?php

/**
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Tracy;

use Tracy;


/**
 * Logger.
 *
 * @author     David Grudl
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

	/** @var callable handler for sending emails */
	public $mailer;

	/** @var BlueScreen */
	private $blueScreen;


	public function __construct($directory, $email = NULL, BlueScreen $blueScreen = NULL)
	{
		$this->directory = $directory;
		$this->email = $email;
		$this->blueScreen = $blueScreen;
		$this->mailer = array($this, 'defaultMailer');
	}


	/**
	 * Logs message or exception to file and sends email notification.
	 * @param  string|\Exception
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

		$exceptionFile = $message instanceof \Exception ? $this->getExceptionFile($message) : NULL;
		$line = $this->formatLogLine($message, $exceptionFile);
		$file = $this->directory . '/' . strtolower($priority ?: self::INFO) . '.log';

		if (!@file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX)) {
			throw new \RuntimeException("Unable to write to log file '$file'. Is directory writable?");
		}

		if ($message instanceof \Exception) {
			$this->logException($message, $exceptionFile);
		}

		if (in_array($priority, array(self::ERROR, self::EXCEPTION, self::CRITICAL), TRUE)) {
			$this->sendEmail($message);
		}

		return $exceptionFile;
	}


	/**
	 * @return string
	 */
	protected function formatMessage($message)
	{
		if ($message instanceof \Exception) {
			while ($message) {
				$tmp[] = ($message instanceof \ErrorException ?
					'Fatal error: ' . $message->getMessage()
					: get_class($message) . ': ' . $message->getMessage()
				) . ' in ' . $message->getFile() . ':' . $message->getLine();
				$message = $message->getPrevious();
			}
			$message = implode($tmp, "\ncaused by ");

		} elseif (!is_string($message)) {
			$message = Dumper::toText($message);
		}

		return trim($message);
	}


	/**
	 * @return string
	 */
	protected function formatLogLine($message, $exceptionFile = NULL)
	{
		return implode(' ', array(
			@date('[Y-m-d H-i-s]'),
			preg_replace('#\s*\r?\n\s*#', ' ', $this->formatMessage($message)),
			' @  ' . Helpers::getSource(),
			$exceptionFile ? ' @@  ' . basename($exceptionFile) : NULL
		));
	}


	/**
	 * @return string
	 */
	protected function getExceptionFile(\Exception $exception)
	{
		$dir = strtr($this->directory . '/', '\\/', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
		$hash = md5(preg_replace('~(Resource id #)\d+~', '$1', $exception));
		foreach (new \DirectoryIterator($this->directory) as $file) {
			if (strpos($file, $hash)) {
				return $dir . $file;
			}
		}
		return $dir . 'exception-' . @date('Y-m-d-H-i-s') . "-$hash.html";
	}


	/**
	 * Logs exception to the file if file doesn't exist.
	 * @return string logged error filename
	 */
	protected function logException(\Exception $exception, $file = NULL)
	{
		$file = $file ?: $this->getExceptionFile($exception);
		if ($handle = @fopen($file, 'x')) {
			ob_start(); // double buffer prevents sending HTTP headers in some PHP
			ob_start(function($buffer) use ($handle) { fwrite($handle, $buffer); }, 4096);
			$bs = $this->blueScreen ?: new BlueScreen;
			$bs->render($exception);
			ob_end_flush();
			ob_end_clean();
			fclose($handle);
		}
		return $file;
	}


	/**
	 * @param  string
	 * @return void
	 */
	protected function sendEmail($message)
	{
		$snooze = is_numeric($this->emailSnooze)
			? $this->emailSnooze
			: @strtotime($this->emailSnooze) - time(); // @ - timezone may not be set

		if ($this->email && $this->mailer
			&& @filemtime($this->directory . '/email-sent') + $snooze < time() // @ - file may not exist
			&& @file_put_contents($this->directory . '/email-sent', 'sent') // @ - file may not be writable
		) {
			call_user_func($this->mailer, $message, implode(', ', (array) $this->email));
		}
	}


	/**
	 * Default mailer.
	 * @param  string
	 * @param  string
	 * @return void
	 * @internal
	 */
	public function defaultMailer($message, $email)
	{
		$host = preg_replace('#[^\w.-]+#', '', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : php_uname('n'));
		$parts = str_replace(
			array("\r\n", "\n"),
			array("\n", PHP_EOL),
			array(
				'headers' => implode("\n", array(
					"From: " . ($this->fromEmail ?: "noreply@$host"),
					'X-Mailer: Tracy',
					'Content-Type: text/plain; charset=UTF-8',
					'Content-Transfer-Encoding: 8bit',
				)) . "\n",
				'subject' => "PHP: An error occurred on the server $host",
				'body' => $this->formatMessage($message) . "\n\nsource: " . Helpers::getSource(),
			)
		);

		mail($email, $parts['subject'], $parts['body'], $parts['headers']);
	}

}
