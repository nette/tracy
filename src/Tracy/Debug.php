<?php

/**
 * Nette Framework
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    http://nette.org/license  Nette license
 * @link       http://nette.org
 * @category   Nette
 * @package    Nette
 */

namespace Nette;

use Nette,
	Nette\Environment;



/**
 * Debug static class.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Nette
 */
final class Debug
{
	/** @var bool determines whether a server is running in production mode */
	public static $productionMode;

	/** @var bool determines whether a server is running in console mode */
	public static $consoleMode;

	/** @var int */
	public static $time;

	/** @var bool is Firebug & FirePHP detected? */
	private static $firebugDetected;

	/** @var bool is AJAX request detected? */
	private static $ajaxDetected;

	/********************* Debug::dump() ****************d*g**/

	/** @var int  how many nested levels of array/object properties display {@link Debug::dump()} */
	public static $maxDepth = 3;

	/** @var int  how long strings display {@link Debug::dump()} */
	public static $maxLen = 150;

	/** @var int  display location? {@link Debug::dump()} */
	public static $showLocation = FALSE;

	/********************* errors and exceptions reporing ****************d*g**/

	/**#@+ server modes {@link Debug::enable()} */
	const DEVELOPMENT = FALSE;
	const PRODUCTION = TRUE;
	const DETECT = NULL;
	/**#@-*/

	/** @var bool determines whether to consider all errors as fatal */
	public static $strictMode = FALSE;

	/** @var array of callbacks specifies the functions that are automatically called after fatal error */
	public static $onFatalError = array();

	/** @var callback */
	public static $mailer = array(__CLASS__, 'defaultMailer');

	/** @var int interval for sending email is 2 days */
	public static $emailSnooze = 172800;

	/** @var bool {@link Debug::enable()} */
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
		'Body' => '[%date%] %message%',
	);

	/********************* debug bar ****************d*g**/

	/** @var bool */
	public static $showBar = TRUE;

	/** @var array */
	private static $panels = array();

	/** @var array payload filled by {@link Debug::barDump()} */
	private static $dumps;

	/** @var array payload filled by {@link Debug::_errorHandler()} */
	private static $errors;

	/** @var array  free counters for your usage */
	public static $counters = array();

	/********************* Firebug extension ****************d*g**/

	/**#@+ FirePHP log priority */
	const LOG = 'LOG';
	const INFO = 'INFO';
	const WARN = 'WARN';
	const ERROR = 'ERROR';
	const TRACE = 'TRACE';
	const EXCEPTION = 'EXCEPTION';
	const GROUP_START = 'GROUP_START';
	const GROUP_END = 'GROUP_END';
	/**#@-*/



	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new \LogicException("Cannot instantiate static class " . get_class($this));
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
		self::$firebugDetected = isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'FirePHP/');
		self::$ajaxDetected = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
		$tab = array(__CLASS__, 'renderTab'); $panel = array(__CLASS__, 'renderPanel');
		self::addPanel(new DebugPanel('time', $tab, $panel));
		self::addPanel(new DebugPanel('memory', $tab, $panel));
		self::addPanel(new DebugPanel('errors', $tab, $panel));
		self::addPanel(new DebugPanel('dumps', $tab, $panel));
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

		$output = "<pre class=\"nette-dump\">" . self::_dump($var, 0) . "</pre>\n";

		if (!$return && self::$showLocation) {
			$trace = debug_backtrace();
			$i = isset($trace[1]['class']) && $trace[1]['class'] === __CLASS__ ? 1 : 0;
			if (isset($trace[$i]['file'], $trace[$i]['line'])) {
				$output = substr_replace($output, ' <small>' . htmlspecialchars("in file {$trace[$i]['file']} on line {$trace[$i]['line']}", ENT_NOQUOTES) . '</small>', -8, 0);
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
				$dump[$key] = self::dump($val, TRUE);
			}
			self::$dumps[] = array('title' => $title, 'dump' => $dump);
		}
		return $var;
	}



	/**
	 * Internal dump() implementation.
	 * @param  mixed  variable to dump
	 * @param  int    current recursion level
	 * @return string
	 */
	private static function _dump(&$var, $level)
	{
		static $tableUtf, $tableBin, $re = '#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u';
		if ($tableUtf === NULL) {
			foreach (range("\x00", "\xFF") as $ch) {
				if (ord($ch) < 32 && strpos("\r\n\t", $ch) === FALSE) $tableUtf[$ch] = $tableBin[$ch] = '\\x' . str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
				elseif (ord($ch) < 127) $tableUtf[$ch] = $tableBin[$ch] = $ch;
				else { $tableUtf[$ch] = $ch; $tableBin[$ch] = '\\x' . dechex(ord($ch)); }
			}
			$tableUtf['\\x'] = $tableBin['\\x'] = '\\\\x';
		}

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
			$s = strtr($s, preg_match($re, $s) || preg_last_error() ? $tableBin : $tableUtf);
			return "<span>string</span>(" . strlen($var) . ") \"$s\"\n";

		} elseif (is_array($var)) {
			$s = "<span>array</span>(" . count($var) . ") ";
			$space = str_repeat($space1 = '   ', $level);

			static $marker;
			if ($marker === NULL) $marker = uniqid("\x00", TRUE);
			if (empty($var)) {

			} elseif (isset($var[$marker])) {
				$s .= "{\n$space$space1*RECURSION*\n$space}";

			} elseif ($level < self::$maxDepth || !self::$maxDepth) {
				$s .= "<code>{\n";
				$var[$marker] = 0;
				foreach ($var as $k => &$v) {
					if ($k === $marker) continue;
					$k = is_int($k) ? $k : '"' . strtr($k, preg_match($re, $k) || preg_last_error() ? $tableBin : $tableUtf) . '"';
					$s .= "$space$space1$k => " . self::_dump($v, $level + 1);
				}
				unset($var[$marker]);
				$s .= "$space}</code>";

			} else {
				$s .= "{\n$space$space1...\n$space}";
			}
			return $s . "\n";

		} elseif (is_object($var)) {
			$arr = (array) $var;
			$s = "<span>object</span>(" . get_class($var) . ") (" . count($arr) . ") ";
			$space = str_repeat($space1 = '   ', $level);

			static $list = array();
			if (empty($arr)) {
				$s .= "{}";

			} elseif (in_array($var, $list, TRUE)) {
				$s .= "{\n$space$space1*RECURSION*\n$space}";

			} elseif ($level < self::$maxDepth || !self::$maxDepth) {
				$s .= "<code>{\n";
				$list[] = $var;
				foreach ($arr as $k => &$v) {
					$m = '';
					if ($k[0] === "\x00") {
						$m = $k[1] === '*' ? ' <span>protected</span>' : ' <span>private</span>';
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$k = strtr($k, preg_match($re, $k) || preg_last_error() ? $tableBin : $tableUtf);
					$s .= "$space$space1\"$k\"$m => " . self::_dump($v, $level + 1);
				}
				array_pop($list);
				$s .= "$space}</code>";

			} else {
				$s .= "{\n$space$space1...\n$space}";
			}
			return $s . "\n";

		} elseif (is_resource($var)) {
			return "<span>resource of type</span>(" . get_resource_type($var) . ")\n";

		} else {
			return "<span>unknown type</span>\n";
		}
	}



	/**
	 * Starts/stops stopwatch.
	 * @param  string  name
	 * @return elapsed seconds
	 */
	public static function timer($name = NULL)
	{
		static $time = array();
		$now = microtime(TRUE);
		$delta = isset($time[$name]) ? $now - $time[$name] : 0;
		$time[$name] = $now;
		return $delta;
	}



	/********************* errors and exceptions reporing ****************d*g**/



	/**
	 * Enables displaying or logging errors and exceptions.
	 * @param  mixed         production, development mode, autodetection or IP address(es).
	 * @param  string        error log file (FALSE disables logging in production mode)
	 * @param  array|string  administrator email or email headers; enables email sending in production mode
	 * @return void
	 */
	public static function enable($mode = NULL, $logFile = NULL, $email = NULL)
	{
		error_reporting(E_ALL | E_STRICT);

		// production/development mode detection
		if (is_bool($mode)) {
			self::$productionMode = $mode;

		} elseif (is_string($mode)) { // IP adresses
			$mode = preg_split('#[,\s]+#', $mode);
		}

		if (is_array($mode)) { // IP adresses
			self::$productionMode = !isset($_SERVER['REMOTE_ADDR']) || !in_array($_SERVER['REMOTE_ADDR'], $mode, TRUE);
		}

		if (self::$productionMode === self::DETECT) {
			if (class_exists('Nette\Environment')) {
				self::$productionMode = Environment::isProduction();

			} elseif (isset($_SERVER['SERVER_ADDR']) || isset($_SERVER['LOCAL_ADDR'])) { // IP address based detection
				$addr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
				$oct = explode('.', $addr);
				self::$productionMode = $addr !== '::1' && (count($oct) !== 4 || ($oct[0] !== '10' && $oct[0] !== '127' && ($oct[0] !== '172' || $oct[1] < 16 || $oct[1] > 31)
					&& ($oct[0] !== '169' || $oct[1] !== '254') && ($oct[0] !== '192' || $oct[1] !== '168')));

			} else {
				self::$productionMode = !self::$consoleMode;
			}
		}

		// logging configuration
		if (self::$productionMode && $logFile !== FALSE) {
			self::$logFile = 'log/php_error.log';

			if (class_exists('Nette\Environment')) {
				if (is_string($logFile)) {
					self::$logFile = Environment::expand($logFile);

				} else try {
					self::$logFile = Environment::expand('%logDir%/php_error.log');

				} catch (\InvalidStateException $e) {
				}

			} elseif (is_string($logFile)) {
				self::$logFile = $logFile;
			}

			ini_set('error_log', self::$logFile);
		}

		// php configuration
		if (function_exists('ini_set')) {
			ini_set('display_errors', !self::$productionMode); // or 'stderr'
			ini_set('html_errors', !self::$logFile && !self::$consoleMode);
			ini_set('log_errors', FALSE);

		} elseif (ini_get('display_errors') != !self::$productionMode && ini_get('display_errors') !== (self::$productionMode ? 'stderr' : 'stdout')) { // intentionally ==
			throw new \NotSupportedException('Function ini_set() must be enabled.');
		}

		self::$sendEmails = self::$logFile && $email;
		if (self::$sendEmails) {
			if (is_string($email)) {
				self::$emailHeaders['To'] = $email;

			} elseif (is_array($email)) {
				self::$emailHeaders = $email + self::$emailHeaders;
			}
		}

		if (!defined('E_DEPRECATED')) {
			define('E_DEPRECATED', 8192);
		}

		if (!defined('E_USER_DEPRECATED')) {
			define('E_USER_DEPRECATED', 16384);
		}

		register_shutdown_function(array(__CLASS__, '_shutdownHandler'));
		set_exception_handler(array(__CLASS__, '_exceptionHandler'));
		set_error_handler(array(__CLASS__, '_errorHandler'));
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
	 * Log user error.
	 * @param  string
	 * @return void
	 */
	public static function log($message)
	{
		error_log(@date('[Y-m-d H-i-s] ') . trim($message) . PHP_EOL, 3, self::$logFile);
	}



	/**
	 * Shutdown handler to execute of the planned activities.
	 * @return void
	 * @internal
	 */
	public static function _shutdownHandler()
	{
		// 1) fatal error handler
		static $types = array(
			E_ERROR => 1,
			E_CORE_ERROR => 1,
			E_COMPILE_ERROR => 1,
			E_PARSE => 1,
		);

		$error = error_get_last();
		if (isset($types[$error['type']])) {
			if (!headers_sent()) { // for PHP < 5.2.4
				header('HTTP/1.1 500 Internal Server Error');
			}

			if (ini_get('html_errors')) {
				$error['message'] = html_entity_decode(strip_tags($error['message']), ENT_QUOTES, 'UTF-8');
			}

			self::processException(new \FatalErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'], NULL), TRUE);
		}


		// 2) debug bar (require HTML & development mode)
		if (self::$showBar && !self::$productionMode && !self::$ajaxDetected && !self::$consoleMode) {
			foreach (headers_list() as $header) {
				if (strncasecmp($header, 'Content-Type:', 13) === 0) {
					if (substr($header, 14, 9) === 'text/html') {
						break;
					}
					return;
				}
			}

			$panels = array();
			foreach (self::$panels as $panel) {
				$panels[] = array(
					'id' => preg_replace('#[^a-z0-9]+#i', '-', $panel->getId()),
					'tab' => $tab = (string) $panel->getTab(),
					'panel' => $tab ? (string) $panel->getPanel() : NULL,
				);
			}

			require __DIR__ . '/Debug.templates/bar.phtml';
		}
	}



	/**
	 * Debug exception handler.
	 * @param  \Exception
	 * @return void
	 * @internal
	 */
	public static function _exceptionHandler(\Exception $exception)
	{
		if (!headers_sent()) {
			header('HTTP/1.1 500 Internal Server Error');
		}
		self::processException($exception, TRUE);
		exit;
	}



	/**
	 * Own error handler.
	 * @param  int    level of the error raised
	 * @param  string error message
	 * @param  string file that the error was raised in
	 * @param  int    line number the error was raised at
	 * @param  array  an array of variables that existed in the scope the error was triggered in
	 * @return bool   FALSE to call normal error handler, NULL otherwise
	 * @throws \FatalErrorException
	 * @internal
	 */
	public static function _errorHandler($severity, $message, $file, $line, $context)
	{
		if ($severity === E_RECOVERABLE_ERROR || $severity === E_USER_ERROR) {
			throw new \FatalErrorException($message, 0, $severity, $file, $line, $context);

		} elseif (($severity & error_reporting()) !== $severity) {
			return NULL; // nothing to do

		} elseif (self::$strictMode) {
			self::_exceptionHandler(new \FatalErrorException($message, 0, $severity, $file, $line, $context), TRUE);
		}

		static $types = array(
			E_WARNING => 'Warning',
			E_USER_WARNING => 'Warning',
			E_NOTICE => 'Notice',
			E_USER_NOTICE => 'Notice',
			E_STRICT => 'Strict standards',
			E_DEPRECATED => 'Deprecated',
			E_USER_DEPRECATED => 'Deprecated',
		);

		$message = 'PHP ' . (isset($types[$severity]) ? $types[$severity] : 'Unknown error') . ": $message in $file:$line";

		if (self::$logFile) {
			if (self::$sendEmails) {
				self::sendEmail($message);
			}
			self::log($message); // log manually, required on some stupid hostings
			return NULL;

		} elseif (!self::$productionMode) {
			if (self::$showBar) {
				self::$errors[] = $message;
			}
			if (self::$firebugDetected && !headers_sent()) {
				self::fireLog(strip_tags($message), self::ERROR);
			}
			return self::$consoleMode || (!self::$showBar && !self::$ajaxDetected) ? FALSE : NULL;
		}

		return FALSE; // call normal error handler
	}



	/**
	 * Logs or displays exception.
	 * @param  \Exception
	 * @param  bool  is writing to standard output buffer allowed?
	 * @return void
	 */
	public static function processException(\Exception $exception, $outputAllowed = FALSE)
	{
		if (!self::$enabled) {
			return;

		} elseif (self::$logFile) {
			try {
				$hash = md5($exception /*5.2*. (method_exists($exception, 'getPrevious') ? $exception->getPrevious() : (isset($exception->previous) ? $exception->previous : ''))*/);
				self::log("PHP Fatal error: Uncaught " . str_replace("Stack trace:\n" . $exception->getTraceAsString(), '', $exception));
				foreach (new \DirectoryIterator(dirname(self::$logFile)) as $entry) {
					if (strpos($entry, $hash)) {
						$skip = TRUE;
						break;
					}
				}
				$file = dirname(self::$logFile) . "/exception " . @date('Y-m-d H-i-s') . " $hash.html";
				if (empty($skip) && self::$logHandle = @fopen($file, 'w')) {
					ob_start(); // double buffer prevents sending HTTP headers in some PHP
					ob_start(array(__CLASS__, '_writeFile'), 1);
					self::_paintBlueScreen($exception);
					ob_end_flush();
					ob_end_clean();
					fclose(self::$logHandle);
				}
				if (self::$sendEmails) {
					self::sendEmail((string) $exception);
				}
			} catch (\Exception $e) {
				if (!headers_sent()) {
					header('HTTP/1.1 500 Internal Server Error');
				}
				echo 'Nette\Debug fatal error: ', get_class($e), ': ', ($e->getCode() ? '#' . $e->getCode() . ' ' : '') . $e->getMessage(), "\n";
				exit;
			}

		} elseif (self::$productionMode) {
			// be quiet

		} elseif (self::$consoleMode) { // dump to console
			if ($outputAllowed) {
				echo "$exception\n";
			}

		} elseif (self::$firebugDetected && self::$ajaxDetected && !headers_sent()) { // AJAX mode
			self::fireLog($exception, self::EXCEPTION);

		} elseif ($outputAllowed) { // dump to browser
			if (!headers_sent()) {
				@ob_end_clean(); while (ob_get_level() && @ob_end_clean());
				/**/header_remove('Content-Encoding');/**/
				/*5.2* if (in_array('Content-Encoding: gzip', headers_list())) header('Content-Encoding: identity', TRUE); // override gzhandler*/
			}
			self::_paintBlueScreen($exception);

		} elseif (self::$firebugDetected && !headers_sent()) {
			self::fireLog($exception, self::EXCEPTION);
		}

		foreach (self::$onFatalError as $handler) {
			call_user_func($handler, $exception);
		}
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
	 * Paint blue screen.
	 * @param  \Exception
	 * @return void
	 * @internal
	 */
	public static function _paintBlueScreen(\Exception $exception)
	{
		$internals = array();
		foreach (array('Nette\Object', 'Nette\ObjectMixin') as $class) {
			if (class_exists($class, FALSE)) {
				$rc = new \ReflectionClass($class);
				$internals[$rc->getFileName()] = TRUE;
			}
		}

		if (class_exists('Nette\Environment', FALSE)) {
			$application = Environment::getServiceLocator()->hasService('Nette\\Application\\Application', TRUE) ? Environment::getServiceLocator()->getService('Nette\\Application\\Application') : NULL;
		}

		require __DIR__ . '/Debug.templates/bluescreen.phtml';
	}



	/**
	 * Redirects output to file.
	 * @param  string
	 * @return string
	 * @internal
	 */
	public static function _writeFile($buffer)
	{
		fwrite(self::$logHandle, $buffer);
	}



	/**
	 * Sends e-mail notification.
	 * @param  string
	 * @return void
	 */
	private static function sendEmail($message)
	{
		$monitorFile = self::$logFile . '.monitor';
		if (@filemtime($monitorFile) + self::$emailSnooze < time()
			&& @file_put_contents($monitorFile, 'sent')) { // intentionally @
			call_user_func(self::$mailer, $message);
		}
	}



	/**
	 * Default mailer.
	 * @param  string
	 * @return void
	 */
	private static function defaultMailer($message)
	{
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] :
				(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');

		$headers = str_replace(
			array('%host%', '%date%', '%message%'),
			array($host, @date('Y-m-d H:i:s', self::$time), $message), // intentionally @
			self::$emailHeaders
		);

		$subject = $headers['Subject'];
		$to = $headers['To'];
		$body = str_replace("\r\n", PHP_EOL, $headers['Body']);
		unset($headers['Subject'], $headers['To'], $headers['Body']);
		$header = '';
		foreach ($headers as $key => $value) {
			$header .= "$key: $value" . PHP_EOL;
		}

		mail($to, $subject, $body, $header);
	}



	/********************* debug bar ****************d*g**/



	/**
	 * Add custom panel.
	 * @param  IDebugPanel
	 * @return void
	 */
	public static function addPanel(IDebugPanel $panel)
	{
		self::$panels[] = $panel;
	}



	/**
	 * Renders default panel.
	 * @param  string
	 * @return void
	 * @internal
	 */
	public static function renderTab($id)
	{
		switch ($id) {
		case 'time':
			require __DIR__ . '/Debug.templates/bar.time.tab.phtml';
			return;
		case 'memory':
			require __DIR__ . '/Debug.templates/bar.memory.tab.phtml';
			return;
		case 'dumps':
			if (!Debug::$dumps) return;
			require __DIR__ . '/Debug.templates/bar.dumps.tab.phtml';
			return;
		case 'errors':
			if (!Debug::$errors) return;
			require __DIR__ . '/Debug.templates/bar.errors.tab.phtml';
		}
	}



	/**
	 * Renders default panel.
	 * @param  string
	 * @return void
	 * @internal
	 */
	public static function renderPanel($id)
	{
		switch ($id) {
		case 'dumps':
			require __DIR__ . '/Debug.templates/bar.dumps.panel.phtml';
			return;
		case 'errors':
			require __DIR__ . '/Debug.templates/bar.errors.panel.phtml';
		}
	}



	/** @deprecated */
	public static function addColophon(){}

	/** @deprecated */
	public static function consoleDump(){}



	/********************* Firebug extension ****************d*g**/



	/**
	 * Sends message to Firebug console.
	 * @param  mixed   message to log
	 * @param  string  priority of message (LOG, INFO, WARN, ERROR, GROUP_START, GROUP_END)
	 * @param  string  optional label
	 * @return bool    was successful?
	 */
	public static function fireLog($message, $priority = self::LOG, $label = NULL)
	{
		if ($message instanceof \Exception) {
			if ($priority !== self::EXCEPTION && $priority !== self::TRACE) {
				$priority = self::TRACE;
			}
			$message = array(
				'Class' => get_class($message),
				'Message' => $message->getMessage(),
				'File' => $message->getFile(),
				'Line' => $message->getLine(),
				'Trace' => $message->getTrace(),
				'Type' => '',
				'Function' => '',
			);
			foreach ($message['Trace'] as & $row) {
				if (empty($row['file'])) $row['file'] = '?';
				if (empty($row['line'])) $row['line'] = '?';
			}
		} elseif ($priority === self::GROUP_START) {
			$label = $message;
			$message = NULL;
		}
		return self::fireSend('FirebugConsole/0.1', self::replaceObjects(array(array('Type' => $priority, 'Label' => $label), $message)));
	}



	/**
	 * Performs Firebug output.
	 * @see http://www.firephp.org
	 * @param  string  structure
	 * @param  array   payload
	 * @return bool    was successful?
	 */
	private static function fireSend($struct, $payload)
	{
		if (self::$productionMode) return NULL;

		if (headers_sent()) return FALSE; // or throw exception?

		header('X-Wf-Protocol-nette: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
		header('X-Wf-nette-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');

		static $structures;
		$index = isset($structures[$struct]) ? $structures[$struct] : ($structures[$struct] = count($structures) + 1);
		header("X-Wf-nette-Structure-$index: http://meta.firephp.org/Wildfire/Structure/FirePHP/$struct");

		$payload = json_encode($payload);
		static $counter;
		foreach (str_split($payload, 4990) as $s) {
			$num = ++$counter;
			header("X-Wf-nette-$index-1-n$num: |$s|\\");
		}
		header("X-Wf-nette-$index-1-n$num: |$s|");

		return TRUE;
	}



	/**
	 * fireLog helper.
	 * @param  mixed
	 * @return mixed
	 */
	static private function replaceObjects($val)
	{
		if (is_object($val)) {
			return 'object ' . get_class($val) . '';

		} elseif (is_string($val)) {
			return @iconv('UTF-16', 'UTF-8//IGNORE', iconv('UTF-8', 'UTF-16//IGNORE', $val)); // intentionally @

		} elseif (is_array($val)) {
			foreach ($val as $k => $v) {
				unset($val[$k]);
				$k = @iconv('UTF-16', 'UTF-8//IGNORE', iconv('UTF-8', 'UTF-16//IGNORE', $k)); // intentionally @
				$val[$k] = self::replaceObjects($v);
			}
		}

		return $val;
	}

}



/**
 * IDebugPanel implementation helper.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Nette
 */
class DebugPanel extends Object implements IDebugPanel
{
	private $id;

	private $tabCb;

	private $panelCb;

	public function __construct($id, $tabCb, $panelCb)
	{
		$this->id = $id;
		$this->tabCb = $tabCb;
		$this->panelCb = $panelCb;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getTab()
	{
		ob_start();
		call_user_func($this->tabCb, $this->id);
		return ob_get_clean();
	}

	public function getPanel()
	{
		ob_start();
		call_user_func($this->panelCb, $this->id);
		return ob_get_clean();
	}

}



Debug::_init();
