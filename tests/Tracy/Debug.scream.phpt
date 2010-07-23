<?php

/**
 * Test: Nette\Debug notices and warnings in scream mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = TRUE;
Debug::$productionMode = FALSE;
Debug::$scream = TRUE;

Debug::enable();

@mktime(); // E_STRICT
@mktime(0, 0, 0, 1, 23, 1978, 1); // E_DEPRECATED
@$x++; // E_NOTICE
@rename('..', '..'); // E_WARNING
@require 'E_COMPILE_WARNING.inc'; // E_COMPILE_WARNING (not working)



__halt_compiler() ?>

------EXPECT------

Strict Standards: mktime(): You should be using the time() function instead in %a% on line %d%

Deprecated: mktime(): The is_dst parameter is deprecated in %a% on line %d%

Notice: Undefined variable: x in %a% on line %d%

Warning: rename(..,..): %A% in %a% on line %d%
