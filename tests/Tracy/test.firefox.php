<?php

require_once '../../Nette/loader.php';

/*use Nette::Debug;*/

unset($_SERVER['REQUEST_TIME'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['Path'], $_SERVER['PATH'], $_SERVER['PATHEXT'], $_SERVER['SERVER_SIGNATURE'], $_SERVER['SERVER_SOFTWARE']);


$arr = array(10, 20, array('key1' => 'val1', 'key2' => TRUE));

// will show in Firebug "Server" tab for the request
Debug::fireDump($arr, 'My var');


// will show in Firebug "Console" tab
Debug::fireLog('Hello World');

Debug::fireLog('Info message', 'INFO');
Debug::fireLog('Warn message', 'WARN');
Debug::fireLog('Error message', 'ERROR');
Debug::fireLog($arr);

Debug::fireLog(array('2 SQL queries took 0.06 seconds', array(
	array('SQL Statement', 'Time', 'Result'),
	array('SELECT * FROM Foo', '0.02', array('row1', 'row2')),
	array('SELECT * FROM Bar', '0.04', array('row1', 'row2'))
)), 'TABLE');


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


// prints headers
Debug::$html = FALSE;
Debug::$maxLen = FALSE;
echo '<pre>';
$headers = headers_list();
sort($headers);
Debug::dump($headers);
