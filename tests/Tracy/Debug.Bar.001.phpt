<?php

/**
 * Test: Nette\Debug Bar in HTML.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;
header('Content-Type: text/html');

Debug::enable();

function shutdown() {
	Assert::match('%A%<!-- Nette Debug Bar -->%A%', ob_get_clean());
}
Assert::handler('shutdown');
