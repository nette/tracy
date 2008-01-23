<h1>Nette::Debug notice test</h1>


<?php
require_once '../../Nette/Debug.php';

/*use Nette::Debug;*/

Debug::enable(E_ALL | E_STRICT);



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
    $x++;
}


first(10, 'any string');