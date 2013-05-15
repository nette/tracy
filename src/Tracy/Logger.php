<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Tracy;

use Tracy;



/**
 * Logger.
 *
 * @author     David Grudl
 */
class Logger
{
	const DEBUG  = 'debug',
		INFO     = 'info',
		WARNING  = 'warning',
		ERROR    = 'error',
		CRITICAL = 'critical';

	/** @var array which constants define e-mail worth errors */
	static public $emailLevels = array(self::ERROR, self::CRITICAL);

	/** @var int interval for sending email is 2 days */
	public static $emailSnooze = 172800;

	/** @var callable handler for sending emails */
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
	public function log($data, $priority = self::INFO)
	{
		if (!is_dir($this->directory)) {
			throw new \RuntimeException("Directory '$this->directory' is not found or is not directory.");
		}

		if (is_array($data)) {

			//
			// If we don't have a message key, we use the 1 index as previously it was the second
			// element in the array which contained to unique error/error_file/line combination.
			//
			// This is used to generate an error ID hash to ensure we see multiple e-mails if
			// errors differ, but don't see multiple e-mails for the same issue.
			//

			$message  = isset($data['message']) ? $data['message'] : $data[1];
			$source   = isset($data['source'])  ? $data['source']  : NULL;
			$detail   = isset($data['detail'])  ? $data['detail']  : NULL;

		} else {
			$message = $data;
		}

		$error_id          = md5(trim($message));
		$log_file          = $this->directory . '/' . strtolower($priority) . '.log';
		$sent_file         = $this->directory . '/email-sent-' . $error_id;
		$sent_expired      = @filemtime($sent_file) + self::$emailSnooze;
		$is_email_priority = in_array($priority, self::$emailLevels);

		$result = error_log(trim($message) . PHP_EOL, 3, $log_file);

		if ($this->email && $this->mailer && $is_email_priority && (@time() > $sent_expired)) {

			if (isset($source)) $message .= PHP_EOL . 'Originating Source: '        . $source;
			if (isset($detail)) $message .= PHP_EOL . 'Detail Information (file): ' . $detail;

			if (@file_put_contents($sent_file, 'sent')) {
				call_user_func($this->mailer, $message, $this->email, $error_id);
			}
		}

		return $result;
	}



	/**
	 * Default mailer.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public static function defaultMailer($message, $email, $error_id = NULL)
	{
		$host = php_uname('n');

		foreach (array('HTTP_HOST','SERVER_NAME', 'HOSTNAME') as $item) {
			if (isset($_SERVER[$item])) {
				$host = $_SERVER[$item]; break;
			}
		}

		$parts = str_replace(
			array("\r\n", "\n"),
			array("\n", PHP_EOL),
			array(
				'headers' => implode("\n", array(
					"From: noreply@$host",
					'X-Mailer: Nette Framework',
					'X-Error-Id: ' . $error_id,
					'Content-Type: text/plain; charset=UTF-8',
					'Content-Transfer-Encoding: 8bit',
				)) . "\n",
				'subject' => "[$host] Error or Exception @ " . @date('Y-m-d H:i:s'), // @ - timezone may not be set
				'body'    => implode("\n", array(
					"Tracy detected an error in one of your script: \n",
					$message . "\n"
				))
			)
		);

		mail($email, $parts['subject'], $parts['body'], $parts['headers']);
	}

}
