<?php

/**
 * Test: Nette\Debug exception in production mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = TRUE;

Debug::enable();

throw new Exception('The my exception', 123);



__halt_compiler() ?>

---EXPECTHEADERS---
Status: 500 Internal Server Error

------EXPECT------
