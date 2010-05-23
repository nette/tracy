<?php

/**
 * Test: Nette\Debug and Environment.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/
/*use Nette\Environment;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



Debug::$consoleMode = FALSE;



dump( Debug::$productionMode, 'Debug::$productionMode' );

output("setting production environment...");

Environment::setMode('production', TRUE);
Debug::enable();

dump( Debug::$productionMode, 'Debug::$productionMode' );



__halt_compiler() ?>

------EXPECT------
Debug::$productionMode: NULL

setting production environment...

Debug::$productionMode: bool(TRUE)
