<?php

/**
 * Test: Nette\Debug eval error in HTML.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 * @assertCode 500
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;
header('Content-Type: text/html');

Debug::enable();

function shutdown() {
	Assert::match(file_get_contents(__DIR__ . '/Debug.error-in-eval.expect'), ob_get_clean());
}
Assert::handler('shutdown');



function first($user, $pass)
{
	eval('trigger_error("The my error", E_USER_ERROR);');
}


first('root', 'xxx');
