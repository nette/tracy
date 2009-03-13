<?php

require_once '../../Nette/loader.php';

/*use Nette\Debug;*/

Debug::$time = 1201042800;
$_SERVER = array_intersect_key($_SERVER, array('PHP_SELF' => 1, 'SCRIPT_NAME' => 1, 'SERVER_ADDR' => 1, 'SERVER_SOFTWARE' => 1, 'HTTP_HOST' => 1, 'DOCUMENT_ROOT' => 1));

die('has no effect now');

Debug::$productionMode = FALSE;
Debug::$consoleMode = FALSE;
Debug::enable();

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