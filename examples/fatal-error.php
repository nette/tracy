<?php

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

// For security reasons, Tracy is visible only on localhost.
// You may force Tracy to run in development mode by passing the Debugger::DEVELOPMENT instead of Debugger::DETECT.
Debugger::enable(Debugger::DETECT, __DIR__ . '/log');

?>
<!DOCTYPE html><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: fatal error demo</h1>

<?php


function first($arg1, $arg2)
{
	second(true, false);
}


function second($arg1, $arg2)
{
	third([1, 2, 3]);
}


function third($arg1)
{
	echo html_special_chars($arg1); // this function doesn't exist
}


if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, Tracy is visible only on localhost. Look into the source code to see how to enable Tracy.</b></p>';
}

first(10, 'any string');
