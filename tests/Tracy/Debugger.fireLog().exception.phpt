<?php

/**
 * Test: Tracy\Debugger::fireLog() and exception.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('FireLogger is not available in CLI mode');
}


// Setup environment
$_SERVER['HTTP_X_FIRELOGGER'] = true;

Debugger::$productionMode = false;


function first($arg1, $arg2)
{
	second(true, false);
}


function second($arg1, $arg2)
{
	third([1, 2, 3]);
}


function third($arg1)
{
	throw new Exception('The my exception', 123);
}


try {
	first(10, 'any string');

} catch (Throwable $e) {
	Debugger::fireLog($e);
}


preg_match('#^FireLogger-de11e-0:(.+)#m', implode("\n", headers_list()), $matches);
Assert::true(isset($matches[1]));
Assert::match('{"logs":[{"name":"PHP","level":"debug","order":0,"time":"%a% ms","template":"Exception: The my exception #123 in %a%\\%ds%Debugger.fireLog().exception.phpt:%d%","message":"","style":"background:#767ab6","pathname":"%a%\\%ds%Debugger.fireLog().exception.phpt","lineno":%d%,"exc_info":["","",[["%a%\\%ds%Debugger.fireLog().exception.phpt",%d%,"third",null],["%a%\\%ds%Debugger.fireLog().exception.phpt",%d%,"second",null],["%a%\\%ds%Debugger.fireLog().exception.phpt",%d%,"first",null]]],"exc_frames":[[[1,2,3]],[true,false],[10,"any string"]],"args":[]}]}', base64_decode($matches[1], true));
