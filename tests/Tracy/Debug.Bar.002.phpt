<?php

/**
 * Test: Nette\Debug Bar in non-HTML mode.
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

Debug::enable();

header('Content-Type: text/plain');

Debug::barDump('value');



__halt_compiler();

------EXPECT------
