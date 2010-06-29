<?php

/**
 * Test: Nette\Debug Bar.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;

Debug::enable();

header('Content-Type: text/html');



__halt_compiler() ?>

------EXPECT------
%A%<div id="nette-debug">%A%