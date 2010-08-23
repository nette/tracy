<?php

/**
 * Test: Nette\Debug Bar in non-HTML mode.
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

header('Content-Type: text/plain');

ob_start();
register_shutdown_function(function() {
	Assert::same('', ob_get_clean());
});
