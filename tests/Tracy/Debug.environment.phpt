<?php

/**
 * Test: Nette\Debug and Environment.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug,
	Nette\Environment;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;



T::dump( Debug::$productionMode, 'Debug::$productionMode' );

T::note("setting production environment...");

Environment::setMode('production', TRUE);
Debug::enable();

T::dump( Debug::$productionMode, 'Debug::$productionMode' );



__halt_compiler() ?>

------EXPECT------
Debug::$productionMode: NULL

setting production environment...

Debug::$productionMode: TRUE
