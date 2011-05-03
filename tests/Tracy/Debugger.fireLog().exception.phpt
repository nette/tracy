<?php

/**
 * Test: Nette\Diagnostics\Debugger::fireLog() and exception.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



// Setup environment
$_SERVER['HTTP_X_FIRELOGGER'] = TRUE;

Debugger::$consoleMode = FALSE;
Debugger::$productionMode = FALSE;



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
	Debugger::fireLog($e);
}


preg_match('#^FireLogger-de11e-0:(.+)#m', implode("\n", headers_list()), $matches);
Assert::true(isset($matches[1]));
Assert::match('{"logs":[{"name":"PHP","level":"debug","order":0,"time":"%a% ms","template":"Exception: The my exception #123 in %a%\\\\Debugger.fireLog().exception.phpt:%d%","message":"","style":"background:#767ab6","pathname":"%a%\\\\Debugger.fireLog().exception.phpt","lineno":%d%,"exc_info":["","",[["%a%\\\\Debugger.fireLog().exception.phpt",%d%,"third",null],["%a%\\\\Debugger.fireLog().exception.phpt",%d%,"second",null],["%a%\\\\Debugger.fireLog().exception.phpt",%d%,"first",null]]],"exc_frames":[[[1,2,3]],[true,false],[10,"any string"]],"args":[]}]}', base64_decode($matches[1]));
