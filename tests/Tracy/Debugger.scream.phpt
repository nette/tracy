<?php

/**
 * Test: Nette\Debug notices and warnings in scream mode.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



Debug::$consoleMode = TRUE;
Debug::$productionMode = FALSE;
Debug::$scream = TRUE;

Debug::enable();

function shutdown() {
	Assert::match('
Strict Standards: mktime(): You should be using the time() function instead in %a% on line %d%

Deprecated: mktime(): The is_dst parameter is deprecated in %a% on line %d%

Notice: Undefined variable: x in %a% on line %d%

Warning: rename(..,..): %A% in %a% on line %d%
', ob_get_clean());
}
Assert::handler('shutdown');



@mktime(); // E_STRICT
@mktime(0, 0, 0, 1, 23, 1978, 1); // E_DEPRECATED
@$x++; // E_NOTICE
@rename('..', '..'); // E_WARNING
@require 'E_COMPILE_WARNING.inc'; // E_COMPILE_WARNING (not working)
