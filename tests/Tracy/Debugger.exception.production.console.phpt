<?php

/**
 * Test: Nette\Debug exception in production & console mode.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



Debug::$consoleMode = TRUE;
Debug::$productionMode = TRUE;

Debug::enable();

function shutdown() {
	Assert::match('ERROR:%A%', ob_get_clean());
}
Assert::handler('shutdown');



throw new Exception('The my exception', 123);
