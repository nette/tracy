<?php

/**
 * Test: Nette\Diagnostics\Debugger Bar in production mode.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$productionMode = TRUE;
header('Content-Type: text/html');

Debugger::enable();

register_shutdown_function(function(){
	Assert::same('', ob_get_clean());
});
ob_start();
