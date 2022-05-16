<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bridges\Nette;

use Nette;
use Tracy;


/**
 * Tracy logger bridge for Nette Mail.
 */
class MailSender
{
	use Nette\SmartObject;

	private Nette\Mail\IMailer $mailer;

	/** @var string|null sender of email notifications */
	private ?string $fromEmail = null;


	public function __construct(Nette\Mail\IMailer $mailer, ?string $fromEmail = null)
	{
		$this->mailer = $mailer;
		$this->fromEmail = $fromEmail;
	}


	public function send(mixed $message, string $email): void
	{
		$host = preg_replace('#[^\w.-]+#', '', $_SERVER['SERVER_NAME'] ?? php_uname('n'));

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
