<?php

/**
 * Test: Nette\Debug exception in production mode.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 * @assertCode 500
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = TRUE;
header('Content-Type: text/html');

Debug::enable();

function shutdown() {
	Assert::match('%A%<h1>Server Error</h1>%A%', ob_get_clean());
}
Assert::handler('shutdown');



throw new Exception('The my exception', 123);
