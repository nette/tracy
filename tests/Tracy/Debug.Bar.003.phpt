<?php

/**
 * Test: Nette\Debug Bar in production mode.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = TRUE;
header('Content-Type: text/html');

Debug::enable();

function shutdown() {
	Assert::same('', ob_get_clean());
}
Assert::handler('shutdown');
