<?php

/**
 * Test: Nette\Debug::consoleDump() in non-HTML mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;

header('Content-Type: text/plain');

Debug::consoleDump('value');



__halt_compiler();

------EXPECT------
