<?php

/**
 * Test: Nette\Debug::fireLog() and exception.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



// Setup environment
$_SERVER['HTTP_X_FIRELOGGER'] = TRUE;

Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;



function first($arg1, $arg2)
{
	second(TRUE, FALSE);
}



function second($arg1, $arg2)
{
	third(array(1, 2, 3));
}


function third($arg1)
{
	throw new Exception('The my exception', 123);
}

try {
	first(10, 'any string');

} catch (Exception $e) {
	Debug::fireLog($e);
}


preg_match('#^FireLogger-de11e-0:(.+)#m', implode("\n", headers_list()), $matches);
Assert::true(isset($matches[1]));
Assert::match('{"logs":[{"name":"PHP","level":"debug","order":0,"time":"%a% ms","template":"Exception: The my exception #123 in %a%\\\\Debug.fireLog().exception.phpt:%d%","message":"","style":"background:#767ab6","exc_info":["The my exception","%a%\\\\Debug.fireLog().exception.phpt",[["%a%\\\\Debug.fireLog().exception.phpt",%d%,"third",null],["%a%\\\\Debug.fireLog().exception.phpt",%d%,"second",null],["%a%\\\\Debug.fireLog().exception.phpt",%d%,"first",null]]],"exc_frames":[[[1,2,3]],[true,false],[10,"any string"]],"args":[],"pathname":"%a%\\\\Debug.fireLog().exception.phpt","lineno":%d%}]}', base64_decode($matches[1]));
