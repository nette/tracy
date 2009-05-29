<?php

require_once '../../Nette/loader.php';

/*use Nette\Debug;*/

Debug::$time = 1201042800;
$_SERVER = array_intersect_key($_SERVER, array('PHP_SELF' => 1, 'SCRIPT_NAME' => 1, 'SERVER_ADDR' => 1, 'SERVER_SOFTWARE' => 1, 'HTTP_HOST' => 1, 'DOCUMENT_ROOT' => 1));

Debug::$consoleMode = FALSE;
Debug::enable();


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
	qwertz($arg1);
}


first(10, 'any string');