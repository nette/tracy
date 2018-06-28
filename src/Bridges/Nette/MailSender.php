<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy\Bridges\Nette;

use Nette;
use Tracy;


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
		if ($this->fromEmail || Nette\Utils\Validators::isEmail("noreply@$host")) {
			$mail->setFrom($this->fromEmail ?: "noreply@$host");
		}
		foreach (explode(',', $email) as $item) {
			$mail->addTo(trim($item));
		}
		$mail->setSubject('PHP: An error occurred on the server ' . $host);
		$mail->setBody(Tracy\Logger::formatMessage($message) . "\n\nsource: " . Tracy\Helpers::getSource());

		$this->mailer->send($mail);
	}
}
