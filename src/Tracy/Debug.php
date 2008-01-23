<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2008 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com/
 *
 * @copyright  Copyright (c) 2004, 2008 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com/
 * @category   Nette
 * @package    Nette
 */

/*namespace Nette;*/



/**
 * Debug static class.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2008 David Grudl
 * @package    Nette
 * @version    $Revision$ $Date$
 */
final class Debug
{
    /** @var bool */
    private static $enabled = FALSE;

    /** @var bool  format the error report as HTML? */
    public static $html;

    /** @var bool  send the error report to the browser? */
    public static $display = TRUE;

    /** @var string  directory where reports are written to files */
    public static $logDir;  // TODO: or $logFileMask ?

    /** @var string  send the error report to the e-mail? */
    public static $email;

    /** @var string  e-mail subject */
    public static $emailSubject = 'PHP error report';

    /** @var array  */
    public static $keysToHide = array('password', 'passwd', 'pass', 'pwd', 'creditcard', 'credit card', 'cc', 'pin');

    /** @var array (not uset yet) */
    private static $panels = array();

    /** @var array  */
    private static $colophons = array();



    /**
     * Static class - cannot be instantiated.
     */
    final public function __construct()
    {
        throw new /*::*/LogicException("Cannot instantiate static class " . get_class($this));
    }



    /**
     * Static class constructor.
     */
    public static function constructStatic()
    {
        self::$html = PHP_SAPI !== 'cli';

        if (!defined('E_RECOVERABLE_ERROR')) {
            define('E_RECOVERABLE_ERROR', 4096);
        }

        if (!defined('E_DEPRECATED')) {
            define('E_DEPRECATED', 8192);
        }
    }



    /**
     * Configure debugger. (EXPERIMENTAL)
     * @param  Config  configuration
     * @return void
     */
    public static function configure(Config $config)
    {
        // $config = Environment::getConfig(__CLASS__);
        if (isset($config->level)) {
            error_reporting($config->level);
        }

        if (isset($config->display)) {
            self::$display = (bool) $config->display;
            ini_set('display_errors', self::$display ? '1' : '0'); // for fatal errors
        }

        if (isset($config->html)) {
            self::$html = (bool) $config->html;
        }

        if (isset($config->logDir)) {
            self::$logDir = Environment::expand($config->logDir);
            ini_set('log_errors', self::$logDir ? '1' : '0'); // for fatal errors
            ini_set('error_log', self::$logDir . '/php.error.log');
        }

        if (isset($config->email)) {
            self::$email = (string) $config->email;
        }

        if (isset($config->emailSubject)) {
            self::$emailSubject = (string) $config->emailSubject;
        }

        if (self::$email || self::$display || self::$logDir) {
            self::enable();
        }
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
        ob_start();
        var_dump($var);
        $output = ob_get_clean();

        if (self::$html) {
            $output = htmlspecialchars($output, ENT_NOQUOTES);
            $output = preg_replace('#\]=&gt;\n\ +([a-z]+)#i', '] => <span>$1</span>', $output);
            $output = preg_replace('#^([a-z]+)#i', '<span>$1</span>', $output);
            $output = "<pre class=\"dump\">$output</pre>\n";
        } else {
            $output = preg_replace('#\]=>\n\ +#i', '] => ', $output) . "\n";
        }

        if (!$return) echo $output;

        return $output;
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



    /********************* error and exception reporing ****************d*g**/



    /**
     * Register error handler routine.
     * @param  int   error_reporting level
     * @return void
     */
    public static function enable($level = NULL)
    {
        /*
        if (self::$display === NULL) {
            require_once dirname(__FILE__) . '/Environment.php';
            self::$display = Environment::getName() !== Environment::PRODUCTION;
        }
        */

        if ($level !== NULL) error_reporting($level);
        set_error_handler(array(__CLASS__, 'errorHandler'));
        set_exception_handler(array(__CLASS__, 'exceptionHandler')); // buggy in PHP 5.2.1
        self::$enabled = TRUE;
    }



    /**
     * Unregister error handler routine.
     * @return void
     */
    public static function disable()
    {
        if (self::$enabled) {
            restore_error_handler();
            restore_exception_handler();
            self::$enabled = FALSE;
        }
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
        self::disable();

        if (self::$html) {
            self::handleMessage(self::blueScreen($exception));
        } else {
            self::handleMessage($exception->__toString() . "\nPHP version " . PHP_VERSION . "\nNette Framework version 0.7\n");
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
     */
    public static function errorHandler($code, $message, $file, $line, $context)
    {
        $fatals = array(
            E_ERROR => 'Fatal error', // unfortunately not catchable
            E_CORE_ERROR => 'Fatal core rrror', // not catchable
            E_COMPILE_ERROR => 'Fatal compile error', // unfortunately not catchable
            E_USER_ERROR => 'Fatal error',
            E_PARSE => 'Parse error', // unfortunately not catchable
            E_RECOVERABLE_ERROR => 'Catchable fatal error', // since PHP 5.2
        );

        if (isset($fatals[$code])) {
            self::disable();

            $trace = debug_backtrace();
            array_shift($trace);
            $type = $fatals[$code];

            if (self::$html) {
                self::handleMessage(self::blueScreen(NULL, $type, $code, $message, $file, $line, $trace, $context));
            } else {
                self::handleMessage("$type '$message' in $file on line $line\nPHP version " . PHP_VERSION . "\nNette Framework version 0.7\n");
            }

            exit;
        }

        if (($code & error_reporting()) === $code) {
            $types = array(
                E_WARNING => 'Warning',
                E_CORE_WARNING => 'Core warning', // not catchable
                E_COMPILE_WARNING => 'Compile warning', // not catchable
                E_USER_WARNING => 'Warning',
                E_NOTICE => 'Notice',
                E_USER_NOTICE => 'Notice',
                E_STRICT => 'Strict standards',
                E_DEPRECATED => 'Deprecated',
            );
            $type = isset($types[$code]) ? $types[$code] : 'Unknown error';

            if (self::$html) {
                $message = "<b>$type:</b> $message in <b>$file</b> on line <b>$line</b>\n<br>";
            } else {
                $message = "$type: $message in $file on line $line\n";
            }

            if (self::$display) {
                echo $message;
            }

            if (self::$logDir) {
                error_log($message);
            }
        }
    }



    /**
     * Handles error message.
     * @param  string
     * @param  bool is fatal
     * @return void
     */
    private static function handleMessage($message)
    {
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        if (self::$logDir) {
            // TODO: add configurable file mask
            $file = self::$logDir . '/report ' . date('Y-m-d H-i-s ') . substr(microtime(FALSE), 2, 6) . (self::$html ? '.html' : '.txt');
            file_put_contents($file, $message);
        }

        if (self::$display) {
            while (ob_get_level() && ob_end_clean());

            echo $message;

            // fix for IE 6
            if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')) {
                $s = " \t\r\n";
                for ($i = 2e3; $i; $i--) echo $s{rand(0, 3)};
            }
        }

        if (self::$email) {
            // TODO: pridat limit na pocet odeslanych emailu denne

            // pro mailer v unixu je treba zamenit \r\n na \n, protoze on si to pak opet zameni za \r\n
            $message = str_replace("\r\n", "\n", $message);
            if (PHP_OS != 'Linux') $message = str_replace("\n", "\r\n", $message);

            mail(self::$email, self::$emailSubject, $message, "Content-Type: text/html; charset=ISO-8859-1");
        }
    }



    /**
     * Paint blue screen.
     * @return string
     */
    public static function blueScreen($exception, $type = NULL, $code = NULL, $message = NULL, $file = NULL, $line = NULL, $trace = NULL, $context = NULL)
    {
        if ($exception) {
            $type = get_class($exception);
            $code = $exception->getCode();
            $message = $exception->getMessage();
            $file = $exception->getFile();
            $line = $exception->getLine();
            $trace = $exception->getTrace();
        }
        $colophons = self::$colophons;
        require_once dirname(__FILE__) . '/Version.php';

        ob_start();
        require dirname(__FILE__) . '/templates/Debug.phtml';
        return ob_get_clean();
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
     * Filters output from self::dump() for sensitive informations.
     * @param  mixed   variable to dump.
     * @param  string  additional key
     * @return string
     */
    private static function safedump($var, $key = NULL)
    {
        if ($key !== NULL && array_search(strtolower($key), self::$keysToHide, TRUE)) {
            return '<i>*** hidden ***</i>';
        }

        return preg_replace(
            '#^(\s*\["(' . implode('|', self::$keysToHide) . ')"\] => <span>string</span>).+#mi',
            '$1 (?) <i>*** hidden ***</i>',
            self::dump($var, TRUE)
        );
    }



    /**
     * Render template.
     */
    private static function openPanel($name, $collaped)
    {
        static $id;
        $id++;
        require dirname(__FILE__) . '/templates/Debug.openpanel.phtml';
    }



    /**
     * Render template.
     */
    private static function closePanel()
    {
        require dirname(__FILE__) . '/templates/Debug.closepanel.phtml';
    }

}



Debug::constructStatic();
