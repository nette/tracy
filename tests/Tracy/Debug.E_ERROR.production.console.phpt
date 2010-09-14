<?php

/**
 * Test: Nette\Debug E_ERROR in production & console mode.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = TRUE;
Debug::$productionMode = TRUE;

Debug::enable();

function shutdown() {
	Assert::match('ERROR:%A%', ob_get_clean());
	die(0);
}
Assert::handler('shutdown');



missing_funcion();
