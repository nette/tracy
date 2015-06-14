<!DOCTYPE html><link rel="stylesheet" href="assets/style.css">

<h1>Tracy Fatal Error demo</h1>

<?php

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;


Debugger::enable(Debugger::DETECT, __DIR__ . '/log');

/*
// Use own theme for Tracy as per the project
$blueScreen = Debugger::getBlueScreen();
$blueScreen->setThemeDir(__DIR__."/assets/")
           ->setTheme('bluescreen.black.css');
*/

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
	missing_funcion();
}


first(10, 'any string');
