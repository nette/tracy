<?php

require_once '../../Nette/loader.php';

/*use Nette::Debug;*/

$_SERVER['REQUEST_TIME'] = 1201042800;
unset($_SERVER['HTTP_USER_AGENT'], $_SERVER['Path'], $_SERVER['PATH'], $_SERVER['PATHEXT'], $_SERVER['SERVER_SIGNATURE'], $_SERVER['SERVER_SOFTWARE']);

Debug::$html = TRUE;
Debug::enable(E_ALL, FALSE);


function first($user, $pass)
{
	eval('trigger_error("The my error", E_USER_ERROR);');
}


first('root', 'prvni heslo');