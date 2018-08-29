<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;


/**
 * Logger which sends emails.
 */
class MailLogger implements ILogger
{
	/** @var string|null name of the directory where errors should be logged */
	public $directory;

	/** @var string|array|null email or emails to which send error notifications */
	public $email;

	/** @var string sender of email notifications */
	public $fromEmail;

	/** @var mixed interval for sending email is 2 days */
	public $emailSnooze = '2 days';

	/** @var callable handler for sending emails */
	public $mailer;


	public function __construct(?string $directory, $email = null)
	{
		$this->directory = $directory;
		$this->email = $email;
		$this->mailer = [$this, 'defaultMailer'];
	}


	public function log($message, string $priority = self::INFO)
	{
		if (!$this->directory) {
			throw new \LogicException('Logging directory is not specified.');
		} elseif (!is_dir($this->directory)) {
			throw new \RuntimeException("Logging directory '$this->directory' is not found or is not directory.");
		}

		if (in_array($priority, [self::ERROR, self::EXCEPTION, self::CRITICAL], true)) {
			$this->sendEmail($message);
			return true;
		}

		return false;
	}


	/**
	 * @param  mixed  $message
	 */
	protected function sendEmail($message): void
	{
		$snooze = is_numeric($this->emailSnooze)
			? $this->emailSnooze
			: @strtotime($this->emailSnooze) - time(); // @ timezone may not be set

		if (
			$this->email
			&& $this->mailer
			&& @filemtime($this->directory . '/email-sent') + $snooze < time() // @ file may not exist
			&& @file_put_contents($this->directory . '/email-sent', 'sent') // @ file may not be writable
		) {
			($this->mailer)($message, implode(', ', (array) $this->email));
		}
	}


	/**
	 * Default mailer.
	 * @param  mixed  $message
	 * @internal
	 */
	public function defaultMailer($message, string $email): void
	{
		$host = preg_replace('#[^\w.-]+#', '', $_SERVER['HTTP_HOST'] ?? php_uname('n'));
		$parts = str_replace(
			["\r\n", "\n"],
			["\n", PHP_EOL],
			[
				'headers' => implode("\n", [
					'From: ' . ($this->fromEmail ?: "noreply@$host"),
					'X-Mailer: Tracy',
					'Content-Type: text/plain; charset=UTF-8',
					'Content-Transfer-Encoding: 8bit',
				]) . "\n",
				'subject' => "PHP: An error occurred on the server $host",
				'body' => static::formatMessage($message) . "\n\nsource: " . Helpers::getSource(),
			]
		);

		mail($email, $parts['subject'], $parts['body'], $parts['headers']);
	}
}
