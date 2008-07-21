<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2008 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2008 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette
 * @version    $Id$
 */

/*namespace Nette;*/



require_once dirname(__FILE__) . '/exceptions.php';

require_once dirname(__FILE__) . '/Framework.php';



/**
 * Debug static class.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2008 David Grudl
 * @package    Nette
 */
final class Debug
{
	/** @var bool  use HTML tags in error messages and dump output? */
	public static $html; // PHP_SAPI !== 'cli'

	/** @var int  Debug::dump() - how many nested levels of array/object properties display Debug::dump()? */
	public static $maxDepth = 3;

	/** @var int  Debug::dump() - how long strings display Debug::dump()? */
	public static $maxLen = 150;

	/** @var bool @see Debug::enable() */
	private static $enabled = FALSE;

	/** @var string  name of the file where script errors should be logged */
	private static $logFile;

	/** @var resource */
	private static $logHandle;

	/** @var bool  send e-mail notifications of errors? */
	private static $sendEmails;

	/** @var string  e-mail headers & body */
	private static $emailHeaders = array(
		'To' => '',
		'From' => 'noreply@%host%',
		'X-Mailer' => 'Nette Framework',
		'Subject' => 'PHP: An error occurred on the server %host%',
		'Body' => '[%date%]',
	);

	/** @var callback */
	public static $mailer = array(__CLASS__, 'sendEmail');

	/** @var float  probability that logfile will be checked */
	public static $emailProbability = 0.01;

	/** @var array  */
	public static $keysToHide = array('password', 'passwd', 'pass', 'pwd', 'creditcard', 'credit card', 'cc', 'pin');

	/** @var array  */
	private static $colophons = array(array(__CLASS__, 'getDefaultColophons'));

	/** @var array  */
	private static $keyFilter = array();

	/** @var int */
	public static $time;

	/** @var array */
	private static $fireCounter;



	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new /*::*/LogicException("Cannot instantiate static class " . get_class($this));
	}



	/********************* useful tools ****************d*g**/



	/**
	 * Dumps information about a variable in readable format.
	 *
	 * @param  mixed  variable to dump.
	 * @param  bool   return output instead of printing it?
	 * @return string
	 */
	public static function dump($var, $return = FALSE)
	{
		self::$keyFilter = FALSE;
		$output = "<pre class=\"dump\">" . self::_dump($var, 0) . "</pre>\n";

		if (!self::$html) {
			$output = htmlspecialchars_decode(strip_tags($output), ENT_NOQUOTES);
		}

		if (!$return) echo $output;

		return $output;
	}



	/**
	 * Internal dump() implementation.
	 *
	 * @param  mixed  variable to dump
	 * @param  int    current recursion level
	 * @return string
	 */
	private static function _dump(&$var, $level)
	{
		if (is_bool($var)) {
			return "<span>bool</span>(" . ($var ? 'TRUE' : 'FALSE') . ")\n";

		} elseif ($var === NULL) {
			return "<span>NULL</span>\n";

		} elseif (is_int($var)) {
			return "<span>int</span>($var)\n";

		} elseif (is_float($var)) {
			return "<span>float</span>($var)\n";

		} elseif (is_string($var)) {
			if (self::$maxLen && strlen($var) > self::$maxLen) {
				$s = htmlSpecialChars(substr($var, 0, self::$maxLen), ENT_NOQUOTES) . ' ... ';
			} else {
				$s = htmlSpecialChars($var, ENT_NOQUOTES);
			}
			return "<span>string</span>(" . strlen($var) . ") \"$s\"\n";

		} elseif (is_array($var)) {
			$s = "<span>array</span>(" . count($var) . ") {\n";
			$space = str_repeat('  ', $level);

			static $marker;
			if ($marker === NULL) $marker = uniqid("\x00", TRUE);
			if (isset($var[$marker])) {
				$s .= "$space  *RECURSION*\n";

			} elseif ($level < self::$maxDepth || !self::$maxDepth) {
				$var[$marker] = 0;
				foreach ($var as $k => &$v) {
					if ($k === $marker) continue;
					$s .= "$space  " . (is_int($k) ? $k : "\"$k\"") . " => ";
					if (self::$keyFilter && is_string($v) && isset(self::$keyFilter[strtolower($k)])) {
						$s .= "<span>string</span>(?) <i>*** hidden ***</i>\n";
					} else {
						$s .= self::_dump($v, $level + 1);
					}
				}
				unset($var[$marker]);
			} else {
				$s .= "$space  ...\n";
			}
			return $s . "$space}\n";

		} elseif (is_object($var)) {
			$arr = (array) $var;
			$s = "<span>object</span>(" . get_class($var) . ") (" . count($arr) . ") {\n";
			$space = str_repeat('  ', $level);

			static $list = array();
			if (in_array($var, $list, TRUE)) {
				$s .= "$space  *RECURSION*\n";

			} elseif ($level < self::$maxDepth || !self::$maxDepth) {
				$list[] = $var;
				foreach ($arr as $k => &$v) {
					$m = '';
					if ($k[0] === "\x00") {
						$m = $k[1] === '*' ? ' <span>protected</span>' : ' <span>private</span>';
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$s .= "$space  \"$k\"$m => ";
					if (self::$keyFilter && is_string($v) && isset(self::$keyFilter[strtolower($k)])) {
						$s .= "<span>string</span>(?) <i>*** hidden ***</i>\n";
					} else {
						$s .= self::_dump($v, $level + 1);
					}
				}
				array_pop($list);
			} else {
				$s .= "$space  ...\n";
			}
			return $s . "$space}\n";

		} elseif (is_resource($var)) {
			return "<span>resource of type</span>(" . get_resource_type($var) . ")\n";
		}
	}



	/**
	 * Starts/stops stopwatch.
	 * @return elapsed seconds
	 */
	public static function timer()
	{
		static $time = 0;
		$now = microtime(TRUE);
		$delta = $now - $time;
		$time = $now;
		return $delta;
	}



	/********************* errors and exceptions reporing ****************d*g**/



	/**
	 * Enables displaying or logging errors and exceptions.
	 * @param  int   error reporting level
	 * @param  bool|string  log to file?
	 * @param  array|string  send emails?
	 * @return void
	 */
	public static function enable($level = E_ALL, $logErrors = NULL, $sendEmails = FALSE)
	{
		if (version_compare(PHP_VERSION, '5.2.1') === 0) {
			throw new /*::*/NotSupportedException(__METHOD__ . ' is not supported in PHP 5.2.1'); // PHP bug #40815
		}

		// Environment auto-detection
		if ($logErrors === NULL && class_exists(/*Nette::*/'Environment')) {
			$logErrors = Environment::isLive();
		}

		if ($level !== NULL) {
			error_reporting($level);
		}

		if (function_exists('ini_set')) {
			ini_set('display_startup_errors', !$logErrors);
			ini_set('display_errors', !$logErrors); // or 'stderr'
			ini_set('html_errors', self::$html);
			ini_set('log_errors', (bool) $logErrors);

		} elseif ($logErrors) {
			// throws error only on production server
			throw new /*::*/NotSupportedException('Function ini_set() is not enabled.');
		}

		if ($logErrors) {
			self::$logFile = is_string($logErrors) ? $logErrors : '%logDir%/php_error.log';
			if (strpos(self::$logFile, '%') !== FALSE) {
				self::$logFile = Environment::expand(self::$logFile);
			}
			ini_set('error_log', self::$logFile);
		}

		self::$sendEmails = $logErrors && $sendEmails;
		if (self::$sendEmails) {
			if (is_string($sendEmails)) {
				self::$emailHeaders['To'] = $sendEmails;

			} elseif (is_array($sendEmails)) {
				self::$emailHeaders = $sendEmails + self::$emailHeaders;
			}
			if (mt_rand() / mt_getrandmax() < self::$emailProbability) {
				self::observeErrorLog();
			}
		}

		if (!defined('E_RECOVERABLE_ERROR')) {
			define('E_RECOVERABLE_ERROR', 4096);
		}

		set_exception_handler(array(__CLASS__, 'exceptionHandler'));
		set_error_handler(array(__CLASS__, 'errorHandler'), E_RECOVERABLE_ERROR | E_USER_ERROR); // E_PARSE & E_ERROR are not catchable
		self::$enabled = TRUE;
	}



	/**
	 * Unregister error handler routine.
	 * @return void
	 */
	public static function isEnabled()
	{
		return self::$enabled;
	}



	/**
	 * Debug exception handler.
	 *
	 * @param  Exception
	 * @return void
	 */
	public static function exceptionHandler(Exception $exception)
	{
		restore_exception_handler();
		restore_error_handler();

		if (!headers_sent()) {
			header('HTTP/1.1 500 Internal Server Error');
		}

		while (ob_get_level() && @ob_end_clean());

		if (self::$logFile) {
			$file = @date('Y-m-d H-i-s', Debug::$time) . strstr(number_format(Debug::$time, 4, '.', ''), '.');
			$file = dirname(self::$logFile) . "/exception $file.html";
			self::$logHandle = @fopen($file, 'x');
			if (self::$logHandle) {
				ob_start(array(__CLASS__, 'writeFile'));
				self::paintBlueScreen($exception);
				ob_end_flush();
				fclose(self::$logHandle);

				$class = get_class($exception);
				error_log("PHP Fatal error:  Uncaught exception '$class' with message '{$exception->getMessage()}' in {$exception->getFile()}:{$exception->getLine()}");

			} else {
				error_log("PHP Fatal error:  Uncaught $exception");
			}
			self::observeErrorLog();

		} elseif (self::$html) {
			self::paintBlueScreen($exception);

		} else {
			echo "$exception\n";
			foreach (self::$colophons as $callback) {
				foreach ((array) call_user_func($callback, 'bluescreen') as $line) echo strip_tags($line) . "\n";
			}
		}

		exit;
	}



	/**
	 * Debug error handler.
	 *
	 * @param  int    level of the error raised
	 * @param  string error message
	 * @param  string file that the error was raised in
	 * @param  int    line number the error was raised at
	 * @param  array  an array of variables that existed in the scope the error was triggered in
	 * @return void
	 * @throws ::FatalErrorException
	 */
	public static function errorHandler($severity, $message, $file, $line, $context)
	{
		$exception = new /*::*/FatalErrorException($message, 0, $severity, $file, $line);
		$exception->context = $context;
		/**/
		if (version_compare(PHP_VERSION, '5.3') === -1) {
			// fix invalid trace in ErrorException - the most ugly code in the otherwise beautiful framework :-)
			$data = serialize($exception);
			$header = 'O:' . strlen(get_class($exception)) . ':"' . get_class($exception) . '"';
			$data = substr_replace($data, 'a', 0, strlen($header));
			$arr = unserialize($data);
			$arr["\x00Exception\x00trace"] = debug_backtrace();
			$data = serialize($arr);
			$data = substr_replace($data, $header, 0, 1);
			$exception = unserialize($data);
		}
		/**/
		throw $exception;
	}



	/**
	 * Paint blue screen.
	 * @param  Exception
	 * @return void
	 */
	public static function paintBlueScreen(Exception $exception)
	{
		$colophons = self::$colophons;
		require dirname(__FILE__) . '/Debug.templates/bluescreen.phtml';
	}



	/**
	 * Add custom descriptions.
	 * @param  callback
	 * @return void
	 */
	public static function addColophon($callback)
	{
		if (!in_array($callback, self::$colophons, TRUE) && is_callable($callback)) {
			self::$colophons[] = $callback;
		}
	}



	/**
	 * Returns default colophons.
	 * @return string
	 * @return array
	 */
	public static function getDefaultColophons($sender)
	{
		if ($sender === 'profiler') {
			$arr[] = 'Elapsed time: ' . sprintf('%0.3f', (microtime(TRUE) - Debug::$time) * 1000) . ' ms';
		}

		if ($sender === 'bluescreen') {
			$arr[] = 'PHP ' . PHP_VERSION;
			if (isset($_SERVER['SERVER_SOFTWARE'])) $arr[] = htmlSpecialChars($_SERVER['SERVER_SOFTWARE']);
			$arr[] = 'Nette Framework ' . Framework::VERSION . ' (revision ' . Framework::REVISION . ')';
			$arr[] = 'Report generated at ' . @strftime('%c', Debug::$time); // intentionally @
		}
		return $arr;
	}



	/**
	 * Redirects output to file.
	 * @param  string
	 * @return string
	 */
	private static function writeFile($buffer)
	{
		fwrite(self::$logHandle, $buffer);
	}



	/**
	 * Notify admin by e-mail if error log changed.
	 * @return void
	 */
	private static function observeErrorLog()
	{
		if (!self::$sendEmails) return;

		$monitorFile = self::$logFile . '.monitor';
		$saved = @file_get_contents($monitorFile); // intentionally @
		$actual = (int) @filemtime(self::$logFile); // intentionally @
		if ($saved === FALSE) {
			file_put_contents($monitorFile, $actual);

		} elseif (is_numeric($saved) && $saved != $actual) { // intentionally ==
			if (file_put_contents($monitorFile, 'e-mail has been sent')) {
				call_user_func(self::$mailer);
			}
		}
	}



	/**
	 * Sends e-mail notification.
	 * @return void
	 */
	private static function sendEmail()
	{
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] :
				(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');

		$headers = str_replace(
			array('%host%', '%date%'),
			array($host, @date('Y-m-d H:i:s', Debug::$time)), // intentionally @
			self::$emailHeaders
		);

		$subject = $headers['Subject'];
		$to = $headers['To'];
		$body = $headers['Body'];
		unset($headers['Subject'], $headers['To'], $headers['Body']);
		$header = '';
		foreach ($headers as $key => $value) {
			$header .= "$key: $value\r\n";
		}

		// pro mailer v unixu je treba zamenit \r\n na \n, protoze on si to pak opet zameni za \r\n
		$body = str_replace("\r\n", "\n", $body);
		if (PHP_OS != 'Linux') $body = str_replace("\n", "\r\n", $body);

		if ($to === 'debug') {
			self::dump(array($to, $subject, $body, $header));

		} else {
			mail($to, $subject, $body, $header);
		}
	}



	/**
	 * Filters output from self::dump() for sensitive informations.
	 * @param  mixed   variable to dump.
	 * @param  string  additional key
	 * @return string
	 */
	private static function safeDump($var, $key = NULL)
	{
		self::$keyFilter = array_change_key_case(array_flip(self::$keysToHide), CASE_LOWER);

		if ($key !== NULL && isset(self::$keyFilter[strtolower($key)])) {
			return '<i>*** hidden ***</i>';
		}

		return "<pre class=\"dump\">" . self::_dump($var, 0) . "</pre>\n";
	}



	/********************* profiler ****************d*g**/



	/**
	 * Enables profiler.
	 * @return void
	 */
	public static function enableProfiler()
	{
		register_shutdown_function(array(__CLASS__, 'paintProfiler'));
	}



	/**
	 * Paint profiler window.
	 * @return void
	 */
	public static function paintProfiler()
	{
		$colophons = self::$colophons;
		require dirname(__FILE__) . '/Debug.templates/profiler.phtml';
	}



	/********************* Firebug extension ****************d*g**/



	/**
	 * Sends variable dump to Firebug tab request/server.
	 * @param  mixed  variable to dump
	 * @param  string unique key
	 * @return void
	 */
	public static function fireDump($var, $key)
	{
		self::fireSend('FirePHP.Dump', array($key => $var));
	}



	/**
	 * Sends message to Firebug console.
	 * @param  mixed   message to log
	 * @param  string  priority of message (LOG, INFO, WARN, ERROR)
	 * @return void
	 */
	public static function fireLog($message, $priority = 'LOG')
	{
		self::fireSend('FirePHP.Firebug.Console', array($message instanceof Exception ?
			array('TRACE', array(
				'Class' => get_class($message),
				'Message' => $message->getMessage(),
				'File' => $message->getFile(),
				'Line' => $message->getLine(),
				'Trace' => $message->getTrace(),
			)) : array($priority, $message)
		));
	}



	/**
	 * Performs Firebug output.
	 * @see http://www.firephp.org
	 * @param  string  service
	 * @param  mixed   arguments
	 * @return void
	 */
	private static function fireSend($method, $arg)
	{
		if (headers_sent()) return; // or throw exception?

		$counter = & self::$fireCounter['main'];
		if (!$counter) {
			header('X-FirePHP-Data-000000000000: {');
			header('X-FirePHP-Data-999999999999: }');
		}

		$s = json_encode($arg);
		$key = & self::$fireCounter[$method];
		if (!$key) {
			$key = str_pad(count(self::$fireCounter), 2, '0', STR_PAD_LEFT);
			header("X-FirePHP-Data-{$key}0000000000: " . ($counter ? ',' : '') . "\"$method\":$s[0]");
			header("X-FirePHP-Data-{$key}9999999999: " . substr($s, -1));
			$s = substr($s, 1, -1);
		} else {
			$s = ',' . substr($s, 1, -1);
		}

		foreach (str_split($s, 5000) as $s) {
			header('X-FirePHP-Data-' . $key . str_pad(++$counter, 10, '0', STR_PAD_LEFT) . ': ' . $s);
		}
	}

}



/**
 * Static class constructor.
 */
Debug::$html = PHP_SAPI !== 'cli';
Debug::$time = microtime(TRUE);
