<?php

/**
 * Test: Nette\Debug exception in production mode.
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
header('Content-Type: text/html');

Debug::enable();

throw new Exception('The my exception', 123);



__halt_compiler() ?>

---EXPECTHEADERS---
Status: 500 Internal Server Error

------EXPECT------
%A%<h1>Server Error</h1>%A%
