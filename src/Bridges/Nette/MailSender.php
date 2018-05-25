<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy\Bridges\Nette;

use Nette;
use Tracy\Dumper;
use Tracy\Helpers;


/**
 * Tracy logger bridge for Nette Mail.
 */
class MailSender
{
	use Nette\SmartObject;

	/** @var Nette\Mail\IMailer */
	private $mailer;

	/** @var string|null sender of email notifications */
	private $fromEmail;


	public function __construct(Nette\Mail\IMailer $mailer, $fromEmail = null)
	{
		$this->mailer = $mailer;
		$this->fromEmail = $fromEmail;
	}


	/**
	 * @param  mixed  $message
	 * @param  string  $email
	 * @return void
	 */
	public function send($message, $email)
	{
		$host = preg_replace('#[^\w.-]+#', '', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : php_uname('n'));

		$mail = new Nette\Mail\Message;
		$mail->setHeader('X-Mailer', 'Tracy');
		$mail->setFrom($this->fromEmail ?: "noreply@$host");
		$mail->addTo($email);
		$mail->setSubject('PHP: An error occurred on the server ' . $host);
		$mail->setBody(static::formatMessage($message) . "\n\nsource: " . Helpers::getSource());

		$this->mailer->send($mail);
	}


	/**
	 * @param  mixed  $message
	 * @return string
	 */
	private static function formatMessage($message)
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
