<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;

/**
 * Base e-mailing notifier
 */
class MailNotifier implements INotifier
{
	/** @var callable handler for sending emails */
	public $mailer;

	/** @var Logger */
	private $logger;

	/** @var string name of the directory where mail-snooze file will be stored */
	private $directory;

	/** @var MessageFormat */
	private $messageFormat;

	public function __construct(Logger $logger, $directory)
	{
		$this->logger = $logger;
		$this->directory = $directory;

		$this->mailer = [$this, 'defaultMailer'];
		$this->messageFormat = new MessageFormat();
	}

	/**
	 * @param  string|\Exception|\Throwable
	 * @param  int
	 * @return void
	 */
	public function notify($message, $priority)
	{
		if (in_array($priority, [ILogger::ERROR, ILogger::EXCEPTION, ILogger::CRITICAL], TRUE)) {
			$this->sendEmail($message);
		}
	}

	/**
	 * @param  string|\Exception|\Throwable
	 * @return void
	 */
	protected function sendEmail($message)
	{
		$snooze = is_numeric($this->logger->emailSnooze)
			? $this->logger->emailSnooze
			: @strtotime($this->logger->emailSnooze) - time(); // @ timezone may not be set

		if ($this->logger->email && $this->mailer
			&& @filemtime($this->directory . '/email-sent') + $snooze < time() // @ file may not exist
			&& @file_put_contents($this->directory . '/email-sent', 'sent') // @ file may not be writable
		) {
			call_user_func($this->mailer, $message, implode(', ', (array) $this->logger->email));
		}
	}


	/**
	 * Default mailer.
	 * @param  string|\Exception|\Throwable
	 * @param  string
	 * @return void
	 * @internal
	 */
	public function defaultMailer($message, $email)
	{
		$host = preg_replace('#[^\w.-]+#', '', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : php_uname('n'));
		$parts = str_replace(
			["\r\n", "\n"],
			["\n", PHP_EOL],
			[
				'headers' => implode("\n", [
						'From: ' . ($this->logger->fromEmail ?: "noreply@$host"),
						'X-Mailer: Tracy',
						'Content-Type: text/plain; charset=UTF-8',
						'Content-Transfer-Encoding: 8bit',
					]) . "\n",
				'subject' => "PHP: An error occurred on the server $host",
				'body' => $this->messageFormat->formatMessage($message) . "\n\nsource: " . Helpers::getSource(),
			]
		);

		mail($email, $parts['subject'], $parts['body'], $parts['headers']);
	}

}
