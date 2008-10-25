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
	/** @var array  free counters for your usage */
	public static $counters = array();

	/** @var bool  use HTML tags in error messages and dump output? */
	public static $html; // PHP_SAPI !== 'cli'

	/** @var int  Debug::dump() - how many nested levels of array/object properties display Debug::dump()? */
	public static $maxDepth = 3;

	/** @var int  Debug::dump() - how long strings display Debug::dump()? */
	public static $maxLen = 150;

	/** @var bool {@link Debug::enable()} */
	private static $enabled = FALSE;

	/** @var bool  send messages to Firebug? */
	public static $useFirebug;

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

	/**#@+ FirePHP log priority */
	const LOG = 'LOG';
	const INFO = 'INFO';
	const WARN = 'WARN';
	const ERROR = 'ERROR';
	/**#@-*/



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
	 * @return mixed  variable or dump
	 */
	public static function dump($var, $return = FALSE)
	{
		self::$keyFilter = FALSE;
		$output = "<pre class=\"dump\">" . self::_dump($var, 0) . "</pre>\n";

		if (!self::$html) {
			$output = htmlspecialchars_decode(strip_tags($output), ENT_NOQUOTES);
		}

		if ($return) {
			return $output;

		} else {
			echo $output;
			return $var;
		}
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

		} else {
			return "<span>unknown type</span>\n";
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
			$logErrors = /*Nette::*/Environment::isLive();
		}

		// Firebug detection
		if (self::$useFirebug === NULL) {
			self::$useFirebug = function_exists('json_encode') && !$logErrors && isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'FirePHP/');
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
			if (is_string($logErrors)) {
				self::$logFile = strpos($logErrors, '%') === FALSE ? $logErrors : /*Nette::*/Environment::expand($logErrors);
			} else {
				try {
					self::$logFile = /*Nette::*/Environment::expand('%logDir%/php_error.log');
				} catch (/*::*/InvalidStateException $e) {
					self::$logFile = 'php_error.log';
				}
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

		if (!defined('E_DEPRECATED')) {
			define('E_DEPRECATED', 8192);
		}

		set_exception_handler(array(__CLASS__, 'exceptionHandler'));
		set_error_handler(array(__CLASS__, 'errorHandler'));
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
		if (!headers_sent()) {
			header('HTTP/1.1 500 Internal Server Error');
		}

		if (self::$logFile) { // log to file
			error_log("PHP Fatal error:  Uncaught $exception");
			$file = @strftime('%d-%b-%Y %H-%M-%S ', Debug::$time) . strstr(number_format(Debug::$time, 4, '~', ''), '~');
			$file = dirname(self::$logFile) . "/exception $file.html";
			self::$logHandle = @fopen($file, 'x');
			if (self::$logHandle) {
				ob_start(array(__CLASS__, 'writeFile'), 1);
				self::paintBlueScreen($exception);
				ob_end_flush();
				fclose(self::$logHandle);
			}
			self::observeErrorLog();

		} elseif (!self::$html || isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
			// console or AJAX mode
			if (self::$useFirebug && !headers_sent()) {
				self::fireLog($exception);

			} else {
				echo "$exception\n";
				foreach (self::$colophons as $callback) {
					foreach ((array) call_user_func($callback, 'bluescreen') as $line) echo strip_tags($line) . "\n";
				}
			}

		} else { // dump to screen
			while (ob_get_level() && @ob_end_clean());
			self::paintBlueScreen($exception);
			exit;
		}
	}



	/**
	 * Own error handler.
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
		static $fatals = array(
			E_ERROR => 1, // unfortunately not catchable
			E_CORE_ERROR => 1, // not catchable
			E_COMPILE_ERROR => 1, // unfortunately not catchable
			E_USER_ERROR => 1,
			E_PARSE => 1, // unfortunately not catchable
			E_RECOVERABLE_ERROR => 1, // since PHP 5.2
		);

		if (isset($fatals[$severity])) {
			throw new /*::*/FatalErrorException($message, 0, $severity, $file, $line, $context);

		} elseif (($severity & error_reporting()) !== $severity) {
			return NULL; // nothing to do

		} elseif (self::$useFirebug && !headers_sent()) {
			$types = array(
				E_WARNING => 'Warning',
				E_USER_WARNING => 'Warning',
				E_NOTICE => 'Notice',
				E_USER_NOTICE => 'Notice',
				E_STRICT => 'Strict standards',
				E_DEPRECATED => 'Deprecated',
			);

			$type = isset($types[$severity]) ? $types[$severity] : 'Unknown error';
			$message = strip_tags($message);
			self::fireLog("$type: $message in $file on line $line", 'WARN');
			return NULL;
		}

		return FALSE; // call normal error handler
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
	 * Redirects output to file.
	 * @param  string
	 * @return string
	 * @internal
	 */
	public static function writeFile($buffer)
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
		if (self::$useFirebug) {
			foreach (self::$colophons as $callback) {
				foreach ((array) call_user_func($callback, 'profiler') as $line) self::fireLog(strip_tags($line));
			}
		}
		if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
			// non AJAX mode
			require dirname(__FILE__) . '/Debug.templates/profiler.phtml';
		}
	}



	/********************* colophons ****************d*g**/



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
	 * @param  string  profiler | bluescreen
	 * @return array
	 */
	public static function getDefaultColophons($sender)
	{
		if ($sender === 'profiler') {
			$arr[] = 'Elapsed time: ' . sprintf('%0.3f', (microtime(TRUE) - Debug::$time) * 1000) . ' ms';

			foreach ((array) self::$counters as $name => $value) {
				if (is_array($value)) $value = implode(', ', $value);
				$arr[] = htmlSpecialChars($name) . ' = <strong>' . htmlSpecialChars($value) . '</strong>';
			}

			$autoloaded = class_exists(/*Nette::Loaders::*/'AutoLoader', FALSE) ? /*Nette::Loaders::*/AutoLoader::$count : 0;
			$s = '<span>' . count(get_included_files()) . '/' .  $autoloaded . ' files</span>, ';

			$exclude = array('stdClass', 'Exception', 'ErrorException', 'Traversable', 'IteratorAggregate', 'Iterator', 'ArrayAccess', 'Serializable', 'Closure');
			foreach (get_loaded_extensions() as $ext) {
				$ref = new ReflectionExtension($ext);
				$exclude = array_merge($exclude, $ref->getClassNames());
			}
			$classes = array_diff(get_declared_classes(), $exclude);
			$intf = array_diff(get_declared_interfaces(), $exclude);
			$func = get_defined_functions();
			$func = (array) @$func['user'];
			$consts = get_defined_constants(TRUE);
			$consts = array_keys((array) @$consts['user']);
			foreach (array('classes', 'intf', 'func', 'consts') as $item) {
				$s .= '<span ' . ($$item ? 'title="' . implode(", ", $$item) . '"' : '') . '>' . count($$item) . ' ' . $item . '</span>, ';
			}
			$arr[] = $s;
		}

		if ($sender === 'bluescreen') {
			$arr[] = 'PHP ' . PHP_VERSION;
			if (isset($_SERVER['SERVER_SOFTWARE'])) $arr[] = htmlSpecialChars($_SERVER['SERVER_SOFTWARE']);
			$arr[] = 'Nette Framework ' . Framework::VERSION . ' (revision ' . Framework::REVISION . ')';
			$arr[] = 'Report generated at ' . @strftime('%c', Debug::$time); // intentionally @
		}
		return $arr;
	}



	/********************* Firebug extension ****************d*g**/



	/**
	 * Sends variable dump to Firebug tab request/server.
	 * @param  mixed   variable to dump
	 * @param  string  unique key
	 * @return bool    was successful?
	 */
	public static function fireDump($var, $key)
	{
		return self::fireSend(2, array((string) $key => $var));
	}



	/**
	 * Sends message to Firebug console.
	 * @param  mixed   message to log
	 * @param  string  priority of message (LOG, INFO, WARN, ERROR, GROUP_START, GROUP_END)
	 * @param  string  optional label
	 * @return bool    was successful?
	 */
	public static function fireLog($message, $priority = self::LOG, $label = NULL)
	{
		if ($message instanceof Exception) {
			$priority = 'TRACE';
			$message = array(
				'Class' => get_class($message),
				'Message' => $message->getMessage(),
				'File' => $message->getFile(),
				'Line' => $message->getLine(),
				'Trace' => self::replaceObjects($message->getTrace()),
			);
		} elseif ($priority === 'GROUP_START') {
			$label = $message;
			$message = NULL;
		}
		return self::fireSend(1, array(array('Type' => $priority, 'Label' => $label), $message));
	}



	/**
	 * Performs Firebug output.
	 * @see http://www.firephp.org
	 * @param  int     structure index
	 * @param  array   payload
	 * @return bool    was successful?
	 */
	private static function fireSend($index, $payload)
	{
		if (headers_sent()) return FALSE; // or throw exception?

		if (!self::$fireCounter) {
			header('X-Wf-Protocol-nette: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
			header('X-Wf-nette-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');
			header('X-Wf-nette-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');
			header('X-Wf-nette-Structure-2: http://meta.firephp.org/Wildfire/Structure/FirePHP/Dump/0.1');
		}

		$payload = json_encode($payload);
		foreach (str_split($payload, 4990) as $s) {
			$num = ++self::$fireCounter;
			header("X-Wf-nette-$index-1-$num: |$s|\\");
		}
		header("X-Wf-nette-$index-1-$num: |$s|");
		header("X-Wf-nette-Index: $num");

		return TRUE;
	}



	/**
	 * fireLog helper
	 * @param  array
	 * @return array
	 */
	static private function replaceObjects($val)
	{
		foreach ($val as $k => $v) {
			if (is_object($v)) {
				$val[$k] = 'object ' . get_class($v) . '';
			} elseif (is_array($v)) {
				$val[$k] = self::replaceObjects($v);
			}
		}
		return $val;
	}

}



/**
 * Static class constructor.
 */
Debug::$html = PHP_SAPI !== 'cli';
Debug::$time = microtime(TRUE);

// if (!function_exists('dump')) { function dump($var, $return = FALSE) { /*Nette::*/Debug::dump($var, $return); } }
