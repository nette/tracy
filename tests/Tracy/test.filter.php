<?php

require_once '../../Nette/loader.php';

/*use Nette::Debug;*/

Debug::enable();
Debug::$html = TRUE;

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