<?php

require_once '../../Nette/loader.php';

/*use Nette::Debug;*/

Debug::$time = 1201042800;
unset($_SERVER['REQUEST_TIME'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['Path'], $_SERVER['PATH'], $_SERVER['PATHEXT'], $_SERVER['SERVER_SIGNATURE'], $_SERVER['SERVER_SOFTWARE']);

Debug::$html = TRUE;
Debug::enable(E_ALL, FALSE);

//Debug::$logDir = 'compress.zlib://' . dirname(__FILE__) . '/log';

define('MY_CONST', 123);

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


first(10, 'any string');