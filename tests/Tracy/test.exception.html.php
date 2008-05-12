<?php

require_once '../../Nette/loader.php';

/*use Nette::Debug;*/

Debug::enable();
Debug::$html = TRUE;

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