<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Diagnostics;

use Nette;



/**
 * Logger.
 *
 * @author     David Grudl
 */
class Logger extends Nette\Object
{
	const DEBUG = 'debug',
		INFO = 'info',
		WARNING = 'warning',
		ERROR = 'error',
		CRITICAL = 'critical';

	/** @var int interval for sending email is 2 days */
	public static $emailSnooze = 172800;

	/** @var callback handler for sending emails */
	public $mailer = array(__CLASS__, 'defaultMailer');

	/** @var string name of the directory where errors should be logged; FALSE means that logging is disabled */
	public $directory;

	/** @var string email to sent error notifications */
	public $email;



	/**
	 * Logs message or exception to file and sends email notification.
	 * @param  string|array
	 * @param  int     one of constant INFO, WARNING, ERROR (sends email), CRITICAL (sends email)
	 * @return bool    was successful?
	 */
	public function log($message, $priority = self::INFO)
	{
		if (!is_dir($this->directory)) {
			throw new Nette\DirectoryNotFoundException("Directory '$this->directory' is not found or is not directory.");
		}

		if (is_array($message)) {
			$message = implode(' ', $message);
		}
		$res = error_log(trim($message) . PHP_EOL, 3, $this->directory . '/' . strtolower($priority) . '.log');

		if (($priority === self::ERROR || $priority === self::CRITICAL) && $this->email && $this->mailer
			&& @filemtime($this->directory . '/email-sent') + self::$emailSnooze < time() // @ - file may not exist
			&& @file_put_contents($this->directory . '/email-sent', 'sent') // @ - file may not be writable
		) {
			call_user_func($this->mailer, $message, $this->email);
		}
		return $res;
	}



	/**
	 * Default mailer.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	private static function defaultMailer($message, $email)
	{
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] :
				(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');

		$parts = str_replace(
			array("\r\n", "\n"),
			array("\n", PHP_EOL),
			array(
				'headers' => "From: noreply@$host\nX-Mailer: Nette Framework\n",
				'subject' => "PHP: An error occurred on the server $host",
				'body' => "[" . @date('Y-m-d H:i:s') . "] $message", // @ - timezone may not be set
			)
		);

		mail($email, $parts['subject'], $parts['body'], $parts['headers']);
	}

}
