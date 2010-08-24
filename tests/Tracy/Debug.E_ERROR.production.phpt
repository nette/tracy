<?php

/**
 * Test: Nette\Debug E_ERROR in production mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 * @assertCode 500
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = TRUE;
header('Content-Type: text/html');

Debug::enable();

function shutdown() {
	Assert::match('%A%<h1>Server Error</h1>%A%', ob_get_clean());
	die(0);
}
Assert::handler('shutdown');



missing_funcion();
