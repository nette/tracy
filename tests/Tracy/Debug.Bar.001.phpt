<?php

/**
 * Test: Nette\Debug Bar in HTML.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;

Debug::enable();

header('Content-Type: text/html');

ob_start();
register_shutdown_function(function() {
	Assert::match('%A%<div id="nette-debug">%A%', ob_get_clean());
});
