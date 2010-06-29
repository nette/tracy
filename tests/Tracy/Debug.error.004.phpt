<?php

/**
 * Test: Nette\Debug error in production mode.
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

missing_funcion();



__halt_compiler() ?>

---EXPECTHEADERS---
Status: 500 Internal Server Error

------EXPECT------
