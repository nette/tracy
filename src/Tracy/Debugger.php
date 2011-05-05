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



require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/../Utils/Html.php';



/**
 * Debugger: displays and logs errors.
 *
 * Behavior is determined by two factors: mode & output
 * - modes: production / development
 * - output: HTML / AJAX / CLI / other (e.g. XML)
 *
 * @author     David Grudl
 */
final class Debugger
{
	/** @var bool in production mode is suppressed any debugging output */
	public static $productionMode;

	/** @var bool in console mode is omitted HTML output */
	public static $consoleMode;

	/** @var int timestamp with microseconds of the start of the request */
	public static $time;

	/** @var bool is AJAX request detected? */
	private static $ajaxDetected;

	/** @var string  requested URI or command line */
	public static $source;

	/** @var string URL pattern mask to open editor */
	public static $editor = 'editor://open/?file=%file&line=%line';

	/********************* Debugger::dump() ****************d*g**/

	/** @var int  how many nested levels of array/object properties display {@link Debugger::dump()} */
	public static $maxDepth = 3;

	/** @var int  how long strings display {@link Debugger::dump()} */
	public static $maxLen = 150;

	/** @var int  display location? {@link Debugger::dump()} */
	public static $showLocation = FALSE;

	/********************* errors and exceptions reporing ****************d*g**/

	/** server modes {@link Debugger::enable()} */
	const DEVELOPMENT = FALSE,
		PRODUCTION = TRUE,
		DETECT = NULL;

	/** @var BlueScreen */
	public static $blueScreen;

	/** @var bool determines whether any error will cause immediate death */
	public static $strictMode = FALSE; // $immediateDeath

	/** @var bool disables the @ (shut-up) operator so that notices and warnings are no longer hidden */
	public static $scream = FALSE;

	/** @var array of callbacks specifies the functions that are automatically called after fatal error */
	public static $onFatalError = array();

	/** @var bool {@link Debugger::enable()} */
	private static $enabled = FALSE;

	/** @var mixed {@link Debugger::tryError()} FALSE means catching is disabled */
	private static $lastError = FALSE;

	/********************* logging ****************d*g**/

	/** @var Logger */
	public static $logger;

	/** @var FireLogger */
	public static $fireLogger;

	/** @var string name of the directory where errors should be logged; FALSE means that logging is disabled */
	public static $logDirectory;

	/** @var string email to sent error notifications */
	public static $email;

	/** @deprecated */
	public static $mailer;

	/** @deprecated */
	public static $emailSnooze;

	/********************* debug bar ****************d*g**/

	/** @var Bar */
	public static $bar;

	/** @var DefaultBarPanel */
	private static $errorPanel;

	/** @var DefaultBarPanel */
	private static $dumpPanel;

	/********************* Firebug extension ****************d*g**/

	/** {@link Debugger::log()} and {@link Debugger::fireLog()} */
	const DEBUG = 'debug',
		INFO = 'info',
		WARNING = 'warning',
		ERROR = 'error',
		CRITICAL = 'critical';



	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new Nette\StaticClassException;
	}



	/**
	 * Static class constructor.
	 * @internal
	 */
	public static function _init()
	{
		self::$time = microtime(TRUE);
		self::$consoleMode = PHP_SAPI === 'cli';
		self::$productionMode = self::DETECT;
		if (self::$consoleMode) {
			self::$source = empty($_SERVER['argv']) ? 'cli' : 'cli: ' . implode(' ', $_SERVER['argv']);
		} else {
			self::$ajaxDetected = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
			if (isset($_SERVER['REQUEST_URI'])) {
				self::$source = (isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https://' : 'http://')
					. (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : ''))
					. $_SERVER['REQUEST_URI'];
			}
		}

		self::$logger = new Logger;
		self::$logDirectory = & self::$logger->directory;
		self::$email = & self::$logger->email;
		self::$mailer = & self::$logger->mailer;
		self::$emailSnooze = & Logger::$emailSnooze;

		self::$fireLogger = new FireLogger;

		self::$blueScreen = new BlueScreen;
		self::$blueScreen->addPanel(function($e) {
			if ($e instanceof Nette\Templating\FilterException) {
				return array(
					'tab' => 'Template',
					'panel' => '<p><b>File:</b> ' . Helpers::editorLink($e->sourceFile, $e->sourceLine)
					. '&nbsp; <b>Line:</b> ' . ($e->sourceLine ? $e->sourceLine : 'n/a') . '</p>'
					. ($e->sourceLine ? '<pre>' . BlueScreen::highlightFile($e->sourceFile, $e->sourceLine) . '</pre>' : '')
				);
			}
		});

		self::$bar = new Bar;
		self::$bar->addPanel(new DefaultBarPanel('time'));
		self::$bar->addPanel(new DefaultBarPanel('memory'));
		self::$bar->addPanel(self::$errorPanel = new DefaultBarPanel('errors')); // filled by _errorHandler()
		self::$bar->addPanel(self::$dumpPanel = new DefaultBarPanel('dumps')); // filled by barDump()
	}



	/********************* errors and exceptions reporing ****************d*g**/



	/**
	 * Enables displaying or logging errors and exceptions.
	 * @param  mixed         production, development mode, autodetection or IP address(es) whitelist.
	 * @param  string        error log directory; enables logging in production mode, FALSE means that logging is disabled
	 * @param  string        administrator email; enables email sending in production mode
	 * @return void
	 */
	public static function enable($mode = NULL, $logDirectory = NULL, $email = NULL)
	{
		error_reporting(E_ALL | E_STRICT);

		// production/development mode detection
		if (is_bool($mode)) {
			self::$productionMode = $mode;

		} elseif (is_string($mode)) { // IP addresses
			$mode = preg_split('#[,\s]+#', "$mode 127.0.0.1 ::1");
		}

		if (is_array($mode)) { // IP addresses whitelist detection
			self::$productionMode = !isset($_SERVER['REMOTE_ADDR']) || !in_array($_SERVER['REMOTE_ADDR'], $mode, TRUE);
		}

		if (self::$productionMode === self::DETECT) {
			if (class_exists('Nette\Environment')) {
				self::$productionMode = Nette\Environment::isProduction();

			} elseif (isset($_SERVER['SERVER_ADDR']) || isset($_SERVER['LOCAL_ADDR'])) { // IP address based detection
				$addrs = array();
				if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { // proxy server detected
					$addrs = preg_split('#,\s*#', $_SERVER['HTTP_X_FORWARDED_FOR']);
				}
				if (isset($_SERVER['REMOTE_ADDR'])) {
					$addrs[] = $_SERVER['REMOTE_ADDR'];
				}
				$addrs[] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
				self::$productionMode = FALSE;
				foreach ($addrs as $addr) {
					$oct = explode('.', $addr);
					if ($addr !== '::1' && (count($oct) !== 4 || ($oct[0] !== '10' && $oct[0] !== '127' && ($oct[0] !== '172' || $oct[1] < 16 || $oct[1] > 31)
						&& ($oct[0] !== '169' || $oct[1] !== '254') && ($oct[0] !== '192' || $oct[1] !== '168')))
					) {
						self::$productionMode = TRUE;
						break;
					}
				}

			} else {
				self::$productionMode = !self::$consoleMode;
			}
		}

		// logging configuration
		if (is_string($logDirectory)) {
			self::$logDirectory = realpath($logDirectory);
			if (self::$logDirectory === FALSE) {
				throw new Nette\DirectoryNotFoundException("Directory '$logDirectory' is not found.");
			}
		} elseif ($logDirectory === FALSE) {
			self::$logDirectory = FALSE;

		} else {
			self::$logDirectory = defined('APP_DIR') ? APP_DIR . '/../log' : getcwd() . '/log';
		}
		if (self::$logDirectory) {
			ini_set('error_log', self::$logDirectory . '/php_error.log');
		}

		// php configuration
		if (function_exists('ini_set')) {
			ini_set('display_errors', !self::$productionMode); // or 'stderr'
			ini_set('html_errors', FALSE);
			ini_set('log_errors', FALSE);

		} elseif (ini_get('display_errors') != !self::$productionMode && ini_get('display_errors') !== (self::$productionMode ? 'stderr' : 'stdout')) { // intentionally ==
			throw new Nette\NotSupportedException('Function ini_set() must be enabled.');
		}

		if ($email) {
			if (!is_string($email)) {
				throw new Nette\InvalidArgumentException('Email address must be a string.');
			}
			self::$email = $email;
		}

		if (!defined('E_DEPRECATED')) {
			define('E_DEPRECATED', 8192);
		}

		if (!defined('E_USER_DEPRECATED')) {
			define('E_USER_DEPRECATED', 16384);
		}

		if (!self::$enabled) {
			register_shutdown_function(array(__CLASS__, '_shutdownHandler'));
			set_exception_handler(array(__CLASS__, '_exceptionHandler'));
			set_error_handler(array(__CLASS__, '_errorHandler'));
			self::$enabled = TRUE;
		}
	}



	/**
	 * Is Debug enabled?
	 * @return bool
	 */
	public static function isEnabled()
	{
		return self::$enabled;
	}



	/**
	 * Logs message or exception to file (if not disabled) and sends email notification (if enabled).
	 * @param  string|Exception
	 * @param  int  one of constant Debugger::INFO, WARNING, ERROR (sends email), CRITICAL (sends email)
	 * @return void
	 */
	public static function log($message, $priority = self::INFO)
	{
		if (self::$logDirectory === FALSE) {
			return;

		} elseif (!self::$logDirectory) {
			throw new Nette\InvalidStateException('Logging directory is not specified in Nette\Diagnostics\Debugger::$logDirectory.');
		}

		if ($message instanceof \Exception) {
			$exception = $message;
			$message = "PHP Fatal error: "
				. ($message instanceof Nette\FatalErrorException
					? $exception->getMessage()
					: "Uncaught exception " . get_class($exception) . " with message '" . $exception->getMessage() . "'")
				. " in " . $exception->getFile() . ":" . $exception->getLine();

			$hash = md5($exception /*5.2*. (method_exists($exception, 'getPrevious') ? $exception->getPrevious() : (isset($exception->previous) ? $exception->previous : ''))*/);
			$exceptionFilename = "exception " . @date('Y-m-d H-i-s') . " $hash.html";
			foreach (new \DirectoryIterator(self::$logDirectory) as $entry) {
				if (strpos($entry, $hash)) {
					$exceptionFilename = NULL; break;
				}
			}
		}

		self::$logger->log(array(
			@date('[Y-m-d H-i-s]'),
			$message,
			self::$source ? ' @  ' . self::$source : NULL,
			!empty($exceptionFilename) ? ' @@  ' . $exceptionFilename : NULL
		), $priority);

		if (!empty($exceptionFilename) && $logHandle = @fopen(self::$logDirectory . '/'. $exceptionFilename, 'w')) {
			ob_start(); // double buffer prevents sending HTTP headers in some PHP
			ob_start(function($buffer) use ($logHandle) { fwrite($logHandle, $buffer); }, 1);
			self::$blueScreen->render($exception);
			ob_end_flush();
			ob_end_clean();
			fclose($logHandle);
		}
	}



	/**
	 * Shutdown handler to catch fatal errors and execute of the planned activities.
	 * @return void
	 * @internal
	 */
	public static function _shutdownHandler()
	{
		// fatal error handler
		static $types = array(
			E_ERROR => 1,
			E_CORE_ERROR => 1,
			E_COMPILE_ERROR => 1,
			E_PARSE => 1,
		);
		$error = error_get_last();
		if (isset($types[$error['type']])) {
			self::_exceptionHandler(new Nette\FatalErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'], NULL), TRUE);
		}

		// debug bar (require HTML & development mode)
		if (self::$bar && !self::$productionMode && !self::$ajaxDetected && !self::$consoleMode
			&& !preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()))
		) {
			self::$bar->render();
		}
	}



	/**
	 * Handler to catch uncaught exception.
	 * @param  \Exception
	 * @return void
	 * @internal
	 */
	public static function _exceptionHandler(\Exception $exception, $drawBar = FALSE)
	{
		if (!headers_sent()) { // for PHP < 5.2.4
			header('HTTP/1.1 500 Internal Server Error');
		}

		$htmlMode = !self::$ajaxDetected && !preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()));

		try {
			if (self::$productionMode) {
				self::log($exception, self::ERROR);

				if (self::$consoleMode) {
					echo "ERROR: the server encountered an internal error and was unable to complete your request.\n";

				} elseif ($htmlMode) {
					require __DIR__ . '/templates/error.phtml';
				}

			} else {
				if (self::$consoleMode) { // dump to console
					echo "$exception\n";

				} elseif ($htmlMode) { // dump to browser
					self::$blueScreen->render($exception);
					if ($drawBar && self::$bar) {
						self::$bar->render();
					}

				} elseif (!self::fireLog($exception, self::ERROR)) { // AJAX or non-HTML mode
					self::log($exception);
				}
			}

			foreach (self::$onFatalError as $handler) {
				call_user_func($handler, $exception);
			}
		} catch (\Exception $e) {
			echo "\nNette\\Debug FATAL ERROR: thrown ", get_class($e), ': ', $e->getMessage(),
				"\nwhile processing ", get_class($exception), ': ', $exception->getMessage(), "\n";
		}
		exit(255);
	}



	/**
	 * Handler to catch warnings and notices.
	 * @param  int    level of the error raised
	 * @param  string error message
	 * @param  string file that the error was raised in
	 * @param  int    line number the error was raised at
	 * @param  array  an array of variables that existed in the scope the error was triggered in
	 * @return bool   FALSE to call normal error handler, NULL otherwise
	 * @throws Nette\FatalErrorException
	 * @internal
	 */
	public static function _errorHandler($severity, $message, $file, $line, $context)
	{
		if (self::$scream) {
			error_reporting(E_ALL | E_STRICT);
		}

		if (self::$lastError !== FALSE && ($severity & error_reporting()) === $severity) { // tryError mode
			self::$lastError = new \ErrorException($message, 0, $severity, $file, $line);
			return NULL;
		}

		if ($severity === E_RECOVERABLE_ERROR || $severity === E_USER_ERROR) {
			throw new Nette\FatalErrorException($message, 0, $severity, $file, $line, $context);

		} elseif (($severity & error_reporting()) !== $severity) {
			return FALSE; // calls normal error handler to fill-in error_get_last()

		} elseif (self::$strictMode && !self::$productionMode) {
			self::_exceptionHandler(new Nette\FatalErrorException($message, 0, $severity, $file, $line, $context));
		}

		static $types = array(
			E_WARNING => 'Warning',
			E_COMPILE_WARNING => 'Warning', // currently unable to handle
			E_USER_WARNING => 'Warning',
			E_NOTICE => 'Notice',
			E_USER_NOTICE => 'Notice',
			E_STRICT => 'Strict standards',
			E_DEPRECATED => 'Deprecated',
			E_USER_DEPRECATED => 'Deprecated',
		);

		$message = 'PHP ' . (isset($types[$severity]) ? $types[$severity] : 'Unknown error') . ": $message";
		$count = & self::$errorPanel->data["$message|$file|$line"];

		if ($count++) { // repeated error
			return NULL;

		} elseif (self::$productionMode) {
			self::log("$message in $file:$line", self::ERROR);
			return NULL;

		} else {
			$ok = self::fireLog(new \ErrorException($message, 0, $severity, $file, $line), self::WARNING);
			return self::$consoleMode || (!self::$bar && !$ok) ? FALSE : NULL;
		}

		return FALSE; // call normal error handler
	}



	/**
	 * Handles exception throwed in __toString().
	 * @param  \Exception
	 * @return void
	 */
	public static function toStringException(\Exception $exception)
	{
		if (self::$enabled) {
			self::_exceptionHandler($exception);
		} else {
			trigger_error($exception->getMessage(), E_USER_ERROR);
		}
	}



	/**
	 * Starts catching potential errors/warnings.
	 * @return void
	 */
	public static function tryError()
	{
		if (!self::$enabled && self::$lastError === FALSE) {
			set_error_handler(array(__CLASS__, '_errorHandler'));
		}
		self::$lastError = NULL;
	}



	/**
	 * Returns catched error/warning message.
	 * @param  \ErrorException  catched error
	 * @return bool
	 */
	public static function catchError(& $error)
	{
		if (!self::$enabled && self::$lastError !== FALSE) {
			restore_error_handler();
		}
		$error = self::$lastError;
		self::$lastError = FALSE;
		return (bool) $error;
	}



	/********************* useful tools ****************d*g**/



	/**
	 * Dumps information about a variable in readable format.
	 * @param  mixed  variable to dump
	 * @param  bool   return output instead of printing it? (bypasses $productionMode)
	 * @return mixed  variable itself or dump
	 */
	public static function dump($var, $return = FALSE)
	{
		if (!$return && self::$productionMode) {
			return $var;
		}

		$output = "<pre class=\"nette-dump\">" . Helpers::htmlDump($var) . "</pre>\n";

		if (!$return) {
			$trace = debug_backtrace();
			$i = !isset($trace[1]['class']) && isset($trace[1]['function']) && $trace[1]['function'] === 'dump' ? 1 : 0;
			if (isset($trace[$i]['file'], $trace[$i]['line']) && is_file($trace[$i]['file'])) {
				$lines = file($trace[$i]['file']);
				preg_match('#dump\((.*)\)#', $lines[$trace[$i]['line'] - 1], $m);
				$output = substr_replace(
					$output,
					' title="' . htmlspecialchars((isset($m[0]) ? "$m[0] \n" : '') . "in file {$trace[$i]['file']} on line {$trace[$i]['line']}") . '"',
					4, 0);

				if (self::$showLocation) {
					$output = substr_replace(
						$output,
						' <small>in ' . Helpers::editorLink($trace[$i]['file'], $trace[$i]['line']) . ":{$trace[$i]['line']}</small>",
						-8, 0);
				}
			}
		}

		if (self::$consoleMode) {
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
	 * Starts/stops stopwatch.
	 * @param  string  name
	 * @return float   elapsed seconds
	 */
	public static function timer($name = NULL)
	{
		static $time = array();
		$now = microtime(TRUE);
		$delta = isset($time[$name]) ? $now - $time[$name] : 0;
		$time[$name] = $now;
		return $delta;
	}



	/**
	 * Dumps information about a variable in Nette Debug Bar.
	 * @param  mixed  variable to dump
	 * @param  string optional title
	 * @return mixed  variable itself
	 */
	public static function barDump($var, $title = NULL)
	{
		if (!self::$productionMode) {
			$dump = array();
			foreach ((is_array($var) ? $var : array('' => $var)) as $key => $val) {
				$dump[$key] = Helpers::clickableDump($val);
			}
			self::$dumpPanel->data[] = array('title' => $title, 'dump' => $dump);
		}
		return $var;
	}



	/**
	 * Sends message to FireLogger console.
	 * @param  mixed   message to log
	 * @return bool    was successful?
	 */
	public static function fireLog($message)
	{
		if (!self::$productionMode) {
			return self::$fireLogger->log($message);
		}
	}



	/** @deprecated */
	public static function addPanel(IBarPanel $panel, $id = NULL)
	{
		self::$bar->addPanel($panel, $id);
	}

}



Debugger::_init();
