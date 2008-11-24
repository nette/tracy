<?php

require_once '../../Nette/loader.php';

/*use Nette\Debug;*/

$_SERVER = array_intersect_key($_SERVER, array('PHP_SELF' => 1, 'SCRIPT_NAME' => 1, 'SERVER_ADDR' => 1, 'SERVER_SOFTWARE' => 1, 'HTTP_HOST' => 1, 'DOCUMENT_ROOT' => 1));
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Gecko/2008070208 Firefox/3.0.1 FirePHP/0.1.0.3';


$arr = array(10, 20, array('key1' => 'val1', 'key2' => TRUE));

// will show in Firebug "Server" tab for the request
Debug::fireDump($arr, 'My var');


// will show in Firebug "Console" tab
Debug::fireLog('Hello World');

Debug::fireLog('Info message', 'INFO');
Debug::fireLog('Warn message', 'WARN');
Debug::fireLog('Error message', 'ERROR');
Debug::fireLog($arr);

Debug::fireLog(
	array(
		array('SQL Statement', 'Time', 'Result'), // table header
		array('SELECT * FROM foo', '0.02', array('field1', 'field2')), // 1. row
		array('SELECT * FROM bar', '0.04', array('field1', 'field2')), // 2. row
	),
	'TABLE',
	'2 SQL queries took 0.06 seconds' // table title
);


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
Debug::$consoleMode = TRUE;
Debug::$maxLen = FALSE;
echo '<pre>';
$headers = headers_list();
sort($headers);
Debug::dump($headers);
