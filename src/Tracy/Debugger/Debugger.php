<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;

use ErrorException;


/**
 * Debugger: displays and logs errors.
 */
class Debugger
{
	public const VERSION = '2.7.0';

	/** server modes for Debugger::enable() */
	public const
		DEVELOPMENT = false,
		PRODUCTION = true,
		DETECT = null;

	public const COOKIE_SECRET = 'tracy-debug';

	/** @var bool in production mode is suppressed any debugging output */
	public static $productionMode = self::DETECT;

	/** @var bool whether to display debug bar in development mode */
	public static $showBar = true;

	/** @var bool whether to send data to FireLogger in development mode */
	public static $showFireLogger = true;

	/** @var int size of reserved memory */
	public static $reservedMemorySize = 500000;

	/** @var bool */
	private static $enabled = false;

	/** @var string|null reserved memory; also prevents double rendering */
	private static $reserved;

	/** @var int initial output buffer level */
	private static $obLevel;

	/********************* errors and exceptions reporting ****************d*g**/

	/** @var bool|int determines whether any error will cause immediate death in development mode; if integer that it's matched against error severity */
	public static $strictMode = false;

	/** @var bool disables the @ (shut-up) operator so that notices and warnings are no longer hidden */
	public static $scream = false;

	/** @var callable[] functions that are automatically called after fatal error */
	public static $onFatalError = [];

	/********************* Debugger::dump() ****************d*g**/

	/** @var int  how many nested levels of array/object properties display by dump() */
	public static $maxDepth = 3;

	/** @var int  how long strings display by dump() */
	public static $maxLength = 150;

	/** @var bool display location by dump()? */
	public static $showLocation = false;

	/** @deprecated */
	public static $maxLen;

	/********************* logging ****************d*g**/

	/** @var string|null name of the directory where errors should be logged */
	public static $logDirectory;

	/** @var int  log bluescreen in production mode for this error severity */
	public static $logSeverity = 0;

	/** @var string|array email(s) to which send error notifications */
	public static $email;

	/** for Debugger::log() and Debugger::fireLog() */
	public const
		DEBUG = ILogger::DEBUG,
		INFO = ILogger::INFO,
		WARNING = ILogger::WARNING,
		ERROR = ILogger::ERROR,
		EXCEPTION = ILogger::EXCEPTION,
		CRITICAL = ILogger::CRITICAL;

	/********************* misc ****************d*g**/

	/** @var int timestamp with microseconds of the start of the request */
	public static $time;

	/** @var string URI pattern mask to open editor */
	public static $editor = 'editor://%action/?file=%file&line=%line&search=%search&replace=%replace';

	/** @var array replacements in path */
	public static $editorMapping = [];

	/** @var string command to open browser (use 'start ""' in Windows) */
	public static $browser;

	/** @var string custom static error template */
	public static $errorTemplate;

	/** @var string[] */
	public static $customCssFiles = [];

	/** @var string[] */
	public static $customJsFiles = [];

	/** @var array|null */
	private static $cpuUsage;

	/********************* services ****************d*g**/

	/** @var BlueScreen */
	private static $blueScreen;

	/** @var Bar */
	private static $bar;

	/** @var ILogger */
	private static $logger;

	/** @var ILogger */
	private static $fireLogger;


	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new \LogicException;
	}


	/**
	 * Enables displaying or logging errors and exceptions.
	 * @param  mixed   $mode  production, development mode, autodetection or IP address(es) whitelist.
	 * @param  string  $logDirectory  error log directory
	 * @param  string|array  $email  administrator email; enables email sending in production mode
	 */
	public static function enable($mode = null, string $logDirectory = null, $email = null): void
	{
		if ($mode !== null || self::$productionMode === null) {
			self::$productionMode = is_bool($mode) ? $mode : !self::detectDebugMode($mode);
		}

		self::$reserved = str_repeat('t', self::$reservedMemorySize);
		self::$time = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
		self::$obLevel = ob_get_level();
		self::$cpuUsage = !self::$productionMode && function_exists('getrusage') ? getrusage() : null;

		// logging configuration
		if ($email !== null) {
			self::$email = $email;
		}
		if ($logDirectory !== null) {
			self::$logDirectory = $logDirectory;
		}
		if (self::$logDirectory) {
			if (!preg_match('#([a-z]+:)?[/\\\\]#Ai', self::$logDirectory)) {
				self::exceptionHandler(new \RuntimeException('Logging directory must be absolute path.'));
				self::$logDirectory = null;
			} elseif (!is_dir(self::$logDirectory)) {
				self::exceptionHandler(new \RuntimeException("Logging directory '" . self::$logDirectory . "' is not found."));
				self::$logDirectory = null;
			}
		}

		// php configuration
		if (function_exists('ini_set')) {
			ini_set('display_errors', self::$productionMode ? '0' : '1'); // or 'stderr'
			ini_set('html_errors', '0');
			ini_set('log_errors', '0');

		} elseif (
			ini_get('display_errors') != !self::$productionMode // intentionally ==
			&& ini_get('display_errors') !== (self::$productionMode ? 'stderr' : 'stdout')
		) {
			self::exceptionHandler(new \RuntimeException("Unable to set 'display_errors' because function ini_set() is disabled."));
		}
		error_reporting(E_ALL);

		if (self::$enabled) {
			return;
		}

		register_shutdown_function([__CLASS__, 'shutdownHandler']);
		set_exception_handler([__CLASS__, 'exceptionHandler']);
		set_error_handler([__CLASS__, 'errorHandler']);

		array_map('class_exists', [Bar::class, BlueScreen::class, DefaultBarPanel::class, Dumper::class,
			FireLogger::class, Helpers::class, Logger::class, ]);

		self::dispatch();
		self::$enabled = true;
	}


	public static function dispatch(): void
	{
		if (self::$productionMode || PHP_SAPI === 'cli') {
			return;

		} elseif (headers_sent($file, $line) || ob_get_length()) {
			throw new \LogicException(
				__METHOD__ . '() called after some output has been sent. '
				. ($file ? "Output started at $file:$line." : 'Try Tracy\OutputDebugger to find where output started.')
			);

		} elseif (self::$enabled && session_status() !== PHP_SESSION_ACTIVE) {
			ini_set('session.use_cookies', '1');
			ini_set('session.use_only_cookies', '1');
			ini_set('session.use_trans_sid', '0');
			ini_set('session.cookie_path', '/');
			ini_set('session.cookie_httponly', '1');
			session_start();
		}

		if (self::getBar()->dispatchAssets()) {
			exit;
		}
	}


	/**
	 * Renders loading <script>
	 */
	public static function renderLoader(): void
	{
		if (!self::$productionMode) {
			self::getBar()->renderLoader();
		}
	}


	public static function isEnabled(): bool
	{
		return self::$enabled;
	}


	/**
	 * Shutdown handler to catch fatal errors and execute of the planned activities.
	 * @internal
	 */
	public static function shutdownHandler(): void
	{
		if (self::$reserved === null) {
			return;
		}
		self::$reserved = null;

		$error = error_get_last();
		if (in_array($error['type'] ?? null, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR, E_USER_ERROR], true)) {
			self::exceptionHandler(
				Helpers::fixStack(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])),
				false
			);

		} elseif (self::$showBar && !self::$productionMode) {
			self::removeOutputBuffers(false);
			try {
				self::getBar()->render();
			} catch (\Throwable $e) {
				self::removeOutputBuffers(true);
				self::getBlueScreen()->render($e);
			}
		}
	}


	/**
	 * Handler to catch uncaught exception.
	 * @internal
	 */
	public static function exceptionHandler(\Throwable $exception, bool $exit = true): void
	{
		if (self::$reserved === null && $exit) {
			return;
		}
		self::$reserved = null;

		if (!headers_sent()) {
			http_response_code(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE ') !== false ? 503 : 500);
			if (Helpers::isHtmlMode()) {
				header('Content-Type: text/html; charset=UTF-8');
			}
		}

		Helpers::improveException($exception);
		self::removeOutputBuffers(true);

		if (self::$productionMode) {
			try {
				self::log($exception, self::EXCEPTION);
			} catch (\Throwable $e) {
			}

			if (Helpers::isHtmlMode()) {
				$logged = empty($e);
				require self::$errorTemplate ?: __DIR__ . '/assets/error.500.phtml';
			} elseif (PHP_SAPI === 'cli') {
				fwrite(STDERR, 'ERROR: application encountered an error and can not continue. '
					. (isset($e) ? "Unable to log error.\n" : "Error was logged.\n"));
			}

		} elseif (!connection_aborted() && (Helpers::isHtmlMode() || Helpers::isAjax())) {
			self::getBlueScreen()->render($exception);
			if (self::$showBar) {
				self::getBar()->render();
			}

		} else {
			self::fireLog($exception);
			try {
				$file = self::log($exception, self::EXCEPTION);
				if ($file && !headers_sent()) {
					header("X-Tracy-Error-Log: $file");
				}
				echo "$exception\n" . ($file ? "(stored in $file)\n" : '');
				if ($file && self::$browser) {
					exec(self::$browser . ' ' . escapeshellarg($file));
				}
			} catch (\Throwable $e) {
				echo "$exception\nUnable to log error: {$e->getMessage()}\n";
			}
		}

		try {
			$e = null;
			foreach (self::$onFatalError as $handler) {
				$handler($exception);
			}
		} catch (\Throwable $e) {
			try {
				self::log($e, self::EXCEPTION);
			} catch (\Throwable $e) {
			}
		}

		if ($exit) {
			exit(255);
		}
	}


	/**
	 * Handler to catch warnings and notices.
	 * @return bool|null   false to call normal error handler, null otherwise
	 * @throws ErrorException
	 * @internal
	 */
	public static function errorHandler(int $severity, string $message, string $file, int $line, array $context = null): ?bool
	{
		if (self::$scream) {
			error_reporting(E_ALL);
		}

		if ($severity === E_RECOVERABLE_ERROR || $severity === E_USER_ERROR) {
			if (Helpers::findTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), '*::__toString')) {
				$previous = isset($context['e']) && $context['e'] instanceof \Throwable ? $context['e'] : null;
				$e = new ErrorException($message, 0, $severity, $file, $line, $previous);
				$e->context = $context;
				self::exceptionHandler($e);
			}

			$e = new ErrorException($message, 0, $severity, $file, $line);
			$e->context = $context;
			throw $e;

		} elseif (($severity & error_reporting()) !== $severity) {
			return false; // calls normal error handler to fill-in error_get_last()

		} elseif (self::$productionMode && ($severity & self::$logSeverity) === $severity) {
			$e = new ErrorException($message, 0, $severity, $file, $line);
			$e->context = $context;
			Helpers::improveException($e);
			try {
				self::log($e, self::ERROR);
			} catch (\Throwable $foo) {
			}
			return null;

		} elseif (
			!self::$productionMode
			&& !isset($_GET['_tracy_skip_error'])
			&& (is_bool(self::$strictMode) ? self::$strictMode : ((self::$strictMode & $severity) === $severity))
		) {
			$e = new ErrorException($message, 0, $severity, $file, $line);
			$e->context = $context;
			$e->skippable = true;
			self::exceptionHandler($e);
		}

		$message = 'PHP ' . Helpers::errorTypeToString($severity) . ': ' . Helpers::improveError($message, (array) $context);
		$count = &self::getBar()->getPanel('Tracy:errors')->data["$file|$line|$message"];

		if ($count++) { // repeated error
			return null;

		} elseif (self::$productionMode) {
			try {
				self::log("$message in $file:$line", self::ERROR);
			} catch (\Throwable $foo) {
			}
			return null;

		} else {
			self::fireLog(new ErrorException($message, 0, $severity, $file, $line));
			return Helpers::isHtmlMode() || Helpers::isAjax() ? null : false; // false calls normal error handler
		}
	}


	private static function removeOutputBuffers(bool $errorOccurred): void
	{
		while (ob_get_level() > self::$obLevel) {
			$status = ob_get_status();
			if (in_array($status['name'], ['ob_gzhandler', 'zlib output compression'], true)) {
				break;
			}
			$fnc = $status['chunk_size'] || !$errorOccurred ? 'ob_end_flush' : 'ob_end_clean';
			if (!@$fnc()) { // @ may be not removable
				break;
			}
		}
	}


	/********************* services ****************d*g**/


	public static function getBlueScreen(): BlueScreen
	{
		if (!self::$blueScreen) {
			self::$blueScreen = new BlueScreen;
			self::$blueScreen->info = [
				'PHP ' . PHP_VERSION,
				$_SERVER['SERVER_SOFTWARE'] ?? null,
				'Tracy ' . self::VERSION,
			];
		}
		return self::$blueScreen;
	}


	public static function getBar(): Bar
	{
		if (!self::$bar) {
			self::$bar = new Bar;
			self::$bar->addPanel($info = new DefaultBarPanel('info'), 'Tracy:info');
			$info->cpuUsage = self::$cpuUsage;
			self::$bar->addPanel(new DefaultBarPanel('errors'), 'Tracy:errors'); // filled by errorHandler()
		}
		return self::$bar;
	}


	public static function setLogger(ILogger $logger): void
	{
		self::$logger = $logger;
	}


	public static function getLogger(): ILogger
	{
		if (!self::$logger) {
			self::$logger = new Logger(self::$logDirectory, self::$email, self::getBlueScreen());
			self::$logger->directory = &self::$logDirectory; // back compatiblity
			self::$logger->email = &self::$email;
		}
		return self::$logger;
	}


	public static function getFireLogger(): ILogger
	{
		if (!self::$fireLogger) {
			self::$fireLogger = new FireLogger;
		}
		return self::$fireLogger;
	}


	/********************* useful tools ****************d*g**/


	/**
	 * Dumps information about a variable in readable format.
	 * @tracySkipLocation
	 * @param  mixed  $var  variable to dump
	 * @param  bool   $return  return output instead of printing it? (bypasses $productionMode)
	 * @return mixed  variable itself or dump
	 */
	public static function dump($var, bool $return = false)
	{
		if ($return) {
			ob_start(function () {});
			Dumper::dump($var, [
				Dumper::DEPTH => self::$maxDepth,
				Dumper::TRUNCATE => self::$maxLength,
			]);
			return ob_get_clean();

		} elseif (!self::$productionMode) {
			Dumper::dump($var, [
				Dumper::DEPTH => self::$maxDepth,
				Dumper::TRUNCATE => self::$maxLength,
				Dumper::LOCATION => self::$showLocation,
			]);
		}

		return $var;
	}


	/**
	 * Starts/stops stopwatch.
	 * @return float   elapsed seconds
	 */
	public static function timer(string $name = null): float
	{
		static $time = [];
		$now = microtime(true);
		$delta = isset($time[$name]) ? $now - $time[$name] : 0;
		$time[$name] = $now;
		return $delta;
	}


	/**
	 * Dumps information about a variable in Tracy Debug Bar.
	 * @tracySkipLocation
	 * @param  mixed  $var
	 * @return mixed  variable itself
	 */
	public static function barDump($var, string $title = null, array $options = [])
	{
		if (!self::$productionMode) {
			static $panel;
			if (!$panel) {
				self::getBar()->addPanel($panel = new DefaultBarPanel('dumps'), 'Tracy:dumps');
			}
			$panel->data[] = ['title' => $title, 'dump' => Dumper::toHtml($var, $options + [
				Dumper::DEPTH => self::$maxDepth,
				Dumper::TRUNCATE => self::$maxLength,
				Dumper::LOCATION => self::$showLocation ?: Dumper::LOCATION_CLASS | Dumper::LOCATION_SOURCE,
				Dumper::LAZY => true,
			])];
		}
		return $var;
	}


	/**
	 * Logs message or exception.
	 * @param  mixed  $message
	 * @return mixed
	 */
	public static function log($message, string $level = ILogger::INFO)
	{
		return self::getLogger()->log($message, $level);
	}


	/**
	 * Sends message to FireLogger console.
	 * @param  mixed  $message
	 */
	public static function fireLog($message): bool
	{
		return !self::$productionMode && self::$showFireLogger
			? self::getFireLogger()->log($message)
			: false;
	}


	/**
	 * Detects debug mode by IP address.
	 * @param  string|array  $list  IP addresses or computer names whitelist detection
	 */
	public static function detectDebugMode($list = null): bool
	{
		$addr = $_SERVER['REMOTE_ADDR'] ?? php_uname('n');
		$secret = isset($_COOKIE[self::COOKIE_SECRET]) && is_string($_COOKIE[self::COOKIE_SECRET])
			? $_COOKIE[self::COOKIE_SECRET]
			: null;
		$list = is_string($list)
			? preg_split('#[,\s]+#', $list)
			: (array) $list;
		if (!isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !isset($_SERVER['HTTP_FORWARDED'])) {
			$list[] = '127.0.0.1';
			$list[] = '::1';
			$list[] = '[::1]'; // workaround for PHP < 7.3.4
		}
		return in_array($addr, $list, true) || in_array("$secret@$addr", $list, true);
	}
}
