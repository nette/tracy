<?php

/**
 * Test: Nette\Debug::fireLog()
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



$arr = array(10, 20.2, TRUE, FALSE, NULL, 'hello', array('key1' => 'val1', 'key2' => TRUE), (object) array('key1' => 'val1', 'key2' => TRUE));

// will show in Firebug "Console" tab
Debug::fireLog('Hello World'); // Debug::DEBUG
Debug::fireLog('Info message', Debug::INFO);
Debug::fireLog('Warn message', Debug::WARNING);
Debug::fireLog('Error message', Debug::ERROR);
Debug::fireLog($arr);
/*
Debug::fireLog(
	array(
		array('SQL Statement', 'Time', 'Result'), // table header
		array('SELECT * FROM foo', '0.02', array('field1', 'field2')), // 1. row
		array('SELECT * FROM bar', '0.04', array('field1', 'field2')), // 2. row
	),
	'TABLE',
	'2 SQL queries took 0.06 seconds' // table title
);
*/

Assert::match('%A%
FireLogger-de11e-0:eyJsb2dzIjpbeyJuYW1lIjoiUEhQIiwibGV2ZWwiOiJkZWJ1ZyIsIm9yZGVyIjowLCJ0aW1lIjoiMDAwMDAwLjUgbXMiLCJ0ZW1wbGF0ZSI6IkhlbGxvIFdvcmxkIiwibWVzc2FnZSI6IiIsInN0eWxlIjoiYmFja2dyb3VuZDojNzY3YWI2IiwiYXJncyI6W10sInBhdGhuYW1lIjoiVzpcXE5ldHRlXFxfbmV0dGVcXHRlc3RzXFxEZWJ1Z1xcRGVidWcuZmlyZUxvZygpLmJhc2ljLnBocHQiLCJsaW5lbm8iOjMwfSx7Im5hbWUiOiJQSFAiLCJsZXZlbCI6ImluZm8iLCJvcmRlciI6MSwidGltZSI6IjAwMDAwMC42IG1zIiwidGVtcGxhdGUiOiJJbmZvIG1lc3NhZ2UiLCJtZXNzYWdlIjoiIiwic3R5bGUiOiJiYWNrZ3JvdW5kOiM3NjdhYjYiLCJhcmdzIjpbXSwicGF0aG5hbWUiOiJXOlxcTmV0dGVcXF9uZXR0ZVxcdGVzdHNcXERlYnVnXFxEZWJ1Zy5maXJlTG9nKCkuYmFzaWMucGhwdCIsImxpbmVubyI6MzF9LHsibmFtZSI6IlBIUCIsImxldmVsIjoid2FybmluZyIsIm9yZGVyIjoyLCJ0aW1lIjoiMDAwMDAwLjYgbXMiLCJ0ZW1wbGF0ZSI6Ildhcm4gbWVzc2FnZSIsIm1lc3NhZ2UiOiIiLCJzdHlsZSI6ImJhY2tncm91bmQ6Izc2N2FiNiIsImFyZ3MiOltdLCJwYXRobmFtZSI6Ilc6XFxOZXR0ZVxcX25ldHRlXFx0ZXN0c1xcRGVidWdcXERlYnVnLmZpcmVMb2coKS5iYXNpYy5waHB0IiwibGluZW5vIjozMn0seyJuYW1lIjoiUEhQIiwibGV2ZWwiOiJlcnJvciIsIm9yZGVyIjozLCJ0aW1lIjoiMDAwMDAwLjcgbXMiLCJ0ZW1wbGF0ZSI6IkVycm9yIG1lc3NhZ2UiLCJtZXNzYWdlIjoiIiwic3R5bGUiOiJiYWNrZ3JvdW5kOiM3NjdhYjYiLCJhcmdzIjpbXSwicGF0aG5hbWUiOiJXOlxcTmV0dGVcXF9uZXR0ZVxcdGVzdHNcXERlYnVnXFxEZWJ1Zy5maXJlTG9nKCkuYmFzaWMucGhwdCIsImxpbmVubyI6MzN9LHsibmFtZSI6IlBIUCIsImxldmVsIjoiZGVidWciLCJvcmRlciI6NCwidGltZSI6IjAwMDAwMC43IG1zIiwidGVtcGxhdGUiOiIiLCJtZXNzYWdlIjoiIiwic3R5bGUiOiJiYWNrZ3JvdW5kOiM3NjdhYjYiLCJhcmdzIjpbWzEwLDIwLjIsdHJ1ZSxmYWxzZSxudWxsLCJoZWxsbyIseyJrZXkxIjoidmFsMSIsImtleTIiOnRydWV9LHsia2V5MSI6InZhbDEiLCJrZXkyIjp0cnVlfV1dLCJwYXRobmFtZSI6Ilc6XFxOZXR0ZVxcX25ldHRlXFx0ZXN0c1xcRGVidWdcXERlYnVnLmZpcmVMb2coKS5iYXNpYy5waHB0IiwibGluZW5vIjozNH1dfQ==
', implode("\r\n", headers_list()));
