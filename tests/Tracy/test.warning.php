<h1>Nette\Debug warning test</h1>


<?php
require_once '../../Nette/loader.php';

/*use Nette\Debug;*/

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
	rename('..', '..');
}


first(10, 'any string');
