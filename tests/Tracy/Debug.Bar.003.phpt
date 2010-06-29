<?php

/**
 * Test: Nette\Debug Bar in production mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = TRUE;

Debug::enable();

header('Content-Type: text/html');

Debug::barDump('value');



__halt_compiler() ?>

------EXPECT------
