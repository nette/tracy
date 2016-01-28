<?php

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

Debugger::enable(Debugger::DETECT, __DIR__ . '/log');

?>
<!DOCTYPE html><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: exception demo</h1>

<?php

function first($arg1, $arg2)
{
	second(TRUE, FALSE);
}

function second($arg1, $arg2)
{
	third([1, 2, 3]);
}

function third($arg1)
{
	throw new Exception('The my exception', 123);
}


first(10, 'any string');
