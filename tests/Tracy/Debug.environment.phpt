<?php

/**
 * Test: Nette\Debug and Environment.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug,
	Nette\Environment;



require __DIR__ . '/../bootstrap.php';



Debug::$consoleMode = FALSE;



Assert::null( Debug::$productionMode );

// setting production environment...

Environment::setMode('production', TRUE);
Debug::enable();

Assert::true( Debug::$productionMode );
