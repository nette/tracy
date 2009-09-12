<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette
 */

/*namespace Nette;*/

/*use Nette\Environment;*/



/**/require_once dirname(__FILE__) . '/compatibility.php';/**/

require_once dirname(__FILE__) . '/exceptions.php';

require_once dirname(__FILE__) . '/Framework.php';



/**
 * Debug static class.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
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

	/** @var array payload filled by {@link Debug::consoleDump()} */
	private static $consoleData;

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

	/** @var array  */
	private static $colophons = array(array(__CLASS__, 'getDefaultColophons'));

	/********************* profiler ****************d*g**/

	/** @var bool {@link Debug::enableProfiler()} */
	private static $enabledProfiler = FALSE;

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
		throw new /*\*/LogicException("Cannot instantiate static class " . get_class($this));
	}



	/**
	 * Static class constructor.
	 */
	public static function init()
	{
		self::$time = microtime(TRUE);
		self::$consoleMode = PHP_SAPI === 'cli';
		self::$productionMode = self::DETECT;
		self::$firebugDetected = isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'FirePHP/');
		self::$ajaxDetected = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
		register_shutdown_function(array(__CLASS__, 'shutdownHandler'));
	}



	/**
	 * Shutdown handler to execute of the planned activities.
	 * @return void
	 * @internal
	 */
	public static function shutdownHandler()
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
				$error['message'] = html_entity_decode(strip_tags($error['message']));
			}

			self::processException(new /*\*/FatalErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'], NULL), TRUE);
		}


		// other activities require HTML & development mode
		if (self::$productionMode) {
			return;
		}
		foreach (headers_list() as $header) {
			if (strncasecmp($header, 'Content-Type:', 13) === 0) {
				if (substr($header, 14, 9) === 'text/html') {
					break;
				}
				return;
			}
		}

		// 2) profiler
		if (self::$enabledProfiler) {
			if (self::$firebugDetected) {
				self::fireLog('Nette profiler', self::GROUP_START);
				foreach (self::$colophons as $callback) {
					foreach ((array) call_user_func($callback, 'profiler') as $line) self::fireLog(strip_tags($line));
				}
				self::fireLog(NULL, self::GROUP_END);
			}

			if (!self::$ajaxDetected) {
				$colophons = self::$colophons;
				require dirname(__FILE__) . '/Debug.templates/profiler.phtml';
			}
		}


		// 3) console
		if (self::$consoleData) {
			$payload = self::$consoleData;
			require dirname(__FILE__) . '/Debug.templates/console.phtml';
		}
	}



	/********************* useful tools ****************d*g**/



	/**
	 * Dumps information about a variable in readable format.
	 *
	 * @param  mixed  variable to dump
	 * @param  bool   return output instead of printing it? (bypasses $productionMode)
	 * @return mixed  variable itself or dump
	 */
	public static function dump($var, $return = FALSE)
	{
		if (!$return && self::$productionMode) {
			return $var;
		}

		$output = "<pre class=\"dump\">" . self::_dump($var, 0) . "</pre>\n";

		if (self::$showLocation) {
			$trace = debug_backtrace();
			if (isset($trace[0]['file'], $trace[0]['line'])) {
				$output = substr_replace($output, ' <small>' . htmlspecialchars("in file {$trace[0]['file']} on line {$trace[0]['line']}", ENT_NOQUOTES) . '</small>', -8, 0);
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
	 * Dumps information about a variable in Nette Debug Console.
	 *
	 * @param  mixed  variable to dump
	 * @param  string optional title
	 * @return mixed  variable itself
	 */
	public static function consoleDump($var, $title = NULL)
	{
		if (!self::$productionMode) {
			self::$consoleData[] = array('title' => $title, 'var' => $var);
		}
		return $var;
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
	 * @param  mixed         production, development mode or autodetection
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
		}
		if (self::$productionMode === self::DETECT) {
			if (class_exists(/*Nette\*/'Environment')) {
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

			if (class_exists(/*Nette\*/'Environment')) {
				if (is_string($logFile)) {
					self::$logFile = Environment::expand($logFile);

				} else try {
					self::$logFile = Environment::expand('%logDir%/php_error.log');

				} catch (/*\*/InvalidStateException $e) {
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
			ini_set('log_errors', (bool) self::$logFile);

		} elseif (ini_get('log_errors') != (bool) self::$logFile || // intentionally ==
			(ini_get('display_errors') != !self::$productionMode && ini_get('display_errors') !== (self::$productionMode ? 'stderr' : 'stdout'))) {
			throw new /*\*/NotSupportedException('Function ini_set() must be enabled.');
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
	 * @param  \Exception
	 * @return void
	 * @internal
	 */
	public static function exceptionHandler(/*\*/Exception $exception)
	{
		if (!headers_sent()) {
			header('HTTP/1.1 500 Internal Server Error');
		}

		self::processException($exception, TRUE);
		exit;
	}



	/**
	 * Own error handler.
	 *
	 * @param  int    level of the error raised
	 * @param  string error message
	 * @param  string file that the error was raised in
	 * @param  int    line number the error was raised at
	 * @param  array  an array of variables that existed in the scope the error was triggered in
	 * @return bool   FALSE to call normal error handler, NULL otherwise
	 * @throws \FatalErrorException
	 * @internal
	 */
	public static function errorHandler($severity, $message, $file, $line, $context)
	{
		if ($severity === E_RECOVERABLE_ERROR || $severity === E_USER_ERROR) {
			throw new /*\*/FatalErrorException($message, 0, $severity, $file, $line, $context);

		} elseif (($severity & error_reporting()) !== $severity) {
			return NULL; // nothing to do

		} elseif (self::$strictMode) {
			if (!headers_sent()) {
				header('HTTP/1.1 500 Internal Server Error');
			}
			self::processException(new /*\*/FatalErrorException($message, 0, $severity, $file, $line, $context), TRUE);
			exit;
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

		$type = isset($types[$severity]) ? $types[$severity] : 'Unknown error';

		if (self::$logFile) {
			if (self::$sendEmails) {
				self::sendEmail("$type: $message in $file on line $line");
			}
			return FALSE; // call normal error handler

		} elseif (!self::$productionMode && self::$firebugDetected && !headers_sent()) {
			$message = strip_tags($message);
			self::fireLog("$type: $message in $file on line $line", self::ERROR);
			return NULL;
		}

		return FALSE; // call normal error handler
	}



	/**
	 * Logs or displays exception.
	 * @param  \Exception
	 * @param  bool  is writing to standard output buffer allowed?
	 * @return void
	 */
	public static function processException(/*\*/Exception $exception, $outputAllowed = FALSE)
	{
		if (self::$logFile) {
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
			if (self::$sendEmails) {
				self::sendEmail((string) $exception);
			}

		} elseif (self::$productionMode) {
			// be quiet

		} elseif (self::$consoleMode) { // dump to console
			if ($outputAllowed) {
				echo "$exception\n";
				foreach (self::$colophons as $callback) {
					foreach ((array) call_user_func($callback, 'bluescreen') as $line) echo strip_tags($line) . "\n";
				}
			}

		} elseif (self::$firebugDetected && self::$ajaxDetected && !headers_sent()) { // AJAX mode
			self::fireLog($exception, self::EXCEPTION);

		} elseif ($outputAllowed) { // dump to browser
			if (!headers_sent()) {
				@ob_end_clean(); while (ob_get_level() && @ob_end_clean());
				/*header_remove('Content-Encoding');*/
				/**/header('Content-Encoding: identity', TRUE);/**/ // override gzhandler
			}
			self::paintBlueScreen($exception);

		} elseif (self::$firebugDetected && !headers_sent()) {
			self::fireLog($exception, self::EXCEPTION);
		}

		foreach (self::$onFatalError as $handler) {
			/**/fixCallback($handler);/**/
			call_user_func($handler, $exception);
		}
	}



	/**
	 * Paint blue screen.
	 * @param  \Exception
	 * @return void
	 * @internal
	 */
	public static function paintBlueScreen(/*\*/Exception $exception)
	{
		$internals = array();
		foreach (array(/*Nette\*/'Object', /*Nette\*/'ObjectMixin') as $class) {
			if (class_exists($class, FALSE)) {
				$rc = new /*\*/ReflectionClass($class);
				$internals[$rc->getFileName()] = TRUE;
			}
		}

		if (class_exists(/*Nette\*/'Environment', FALSE)) {
			$application = Environment::getServiceLocator()->hasService('Nette\Application\Application', TRUE) ? Environment::getServiceLocator()->getService('Nette\Application\Application') : NULL;
		}

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
	 * Sends e-mail notification.
	 * @param  string
	 * @return void
	 */
	private static function sendEmail($message)
	{
		$monitorFile = self::$logFile . '.monitor';
		if (!is_file($monitorFile)) {
			if (@file_put_contents($monitorFile, 'e-mail has been sent')) { // intentionally @
				call_user_func(self::$mailer, $message);
			}
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
			array($host, @date('Y-m-d H:i:s', Debug::$time), $message), // intentionally @
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

		// we need to change \r\n to \n because Unix mailer changes it back to \r\n
		$body = str_replace("\r\n", "\n", $body);
		if (PHP_OS != 'Linux') $body = str_replace("\n", "\r\n", $body);

		mail($to, $subject, $body, $header);
	}



	/********************* profiler ****************d*g**/



	/**
	 * Enables profiler.
	 * @return void
	 */
	public static function enableProfiler()
	{
		self::$enabledProfiler = TRUE;
	}



	/**
	 * Disables profiler.
	 * @return void
	 */
	public static function disableProfiler()
	{
		self::$enabledProfiler = FALSE;
	}



	/********************* colophons ****************d*g**/



	/**
	 * Add custom descriptions.
	 * @param  callback
	 * @return void
	 */
	public static function addColophon($callback)
	{
		/**/fixCallback($callback);/**/
		if (!is_callable($callback)) {
			$able = is_callable($callback, TRUE, $textual);
			throw new /*\*/InvalidArgumentException("Colophon handler '$textual' is not " . ($able ? 'callable.' : 'valid PHP callback.'));
		}

		if (!in_array($callback, self::$colophons, TRUE)) {
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
			$arr[] = 'Elapsed time: <b>' . number_format((microtime(TRUE) - Debug::$time) * 1000, 1, '.', ' ') . '</b> ms | Allocated memory: <b>' . number_format(memory_get_peak_usage() / 1000, 1, '.', ' ') . '</b> kB';

			foreach ((array) self::$counters as $name => $value) {
				if (is_array($value)) $value = implode(', ', $value);
				$arr[] = htmlSpecialChars($name) . ' = <strong>' . htmlSpecialChars($value) . '</strong>';
			}

			$autoloaded = class_exists(/*Nette\Loaders\*/'AutoLoader', FALSE) ? /*Nette\Loaders\*/AutoLoader::$count : 0;
			$s = '<span>' . count(get_included_files()) . '/' .  $autoloaded . ' files</span>, ';

			$exclude = array('stdClass', 'Exception', 'ErrorException', 'Traversable', 'IteratorAggregate', 'Iterator', 'ArrayAccess', 'Serializable', 'Closure');
			foreach (get_loaded_extensions() as $ext) {
				$ref = new /*\*/ReflectionExtension($ext);
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
			$arr[] = 'Report generated at ' . @date('Y/m/d H:i:s', Debug::$time); // intentionally @
			if (isset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])) {
				$url = (isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https://' : 'http://') . htmlSpecialChars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
				$arr[] = '<a href="' . $url . '">' . $url . '</a>';
			}
			$arr[] = 'PHP ' . htmlSpecialChars(PHP_VERSION);
			if (isset($_SERVER['SERVER_SOFTWARE'])) $arr[] = htmlSpecialChars($_SERVER['SERVER_SOFTWARE']);
			$arr[] = htmlSpecialChars(Framework::NAME . ' ' . Framework::VERSION) . ' <i>(revision ' . htmlSpecialChars(Framework::REVISION) . ')</i>';
		}
		return $arr;
	}



	/********************* Firebug extension ****************d*g**/



	/**
	 * Sends variable dump to Firebug tab request/server.
	 * @param  mixed  variable to dump
	 * @param  string unique key
	 * @return mixed  variable itself
	 */
	public static function fireDump($var, $key)
	{
		self::fireSend(2, array((string) $key => $var));
		return $var;
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
		if ($message instanceof /*\*/Exception) {
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
		return self::fireSend(1, self::replaceObjects(array(array('Type' => $priority, 'Label' => $label), $message)));
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
		if (self::$productionMode) return NULL;

		if (headers_sent()) return FALSE; // or throw exception?

		header('X-Wf-Protocol-nette: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
		header('X-Wf-nette-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');

		if ($index === 1) {
			header('X-Wf-nette-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');

		} elseif ($index === 2) {
			header('X-Wf-nette-Structure-2: http://meta.firephp.org/Wildfire/Structure/FirePHP/Dump/0.1');
		}

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



Debug::init();

// hint:
// if (!function_exists('dump')) { function dump($var, $return = FALSE) { return /*\Nette\*/Debug::dump($var, $return); } }
