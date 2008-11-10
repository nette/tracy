<?php

require_once '../../Nette/loader.php';

/*use Nette\Debug;*/

Debug::$time = 1201042800;
unset($_SERVER['REQUEST_TIME'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['Path'], $_SERVER['PATH'], $_SERVER['PATHEXT'], $_SERVER['SERVER_SIGNATURE'], $_SERVER['SERVER_SOFTWARE']);

Debug::$html = TRUE;
Debug::enable(E_ALL, FALSE);

define('PASS', 'tajne heslo');

function first($user, $pass)
{
	$struct = (object) array(
		'arr' => array(
			'password' => 'druhe heslo',
		),
	);
	trigger_error('The my error', E_USER_ERROR);
}



first('root', 'prvni heslo');