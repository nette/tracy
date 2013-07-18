<?php

/**
 * Test: Tracy\Debugger notices and warnings in scream mode.
 *
 * @author     David Grudl
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
Debugger::$scream = TRUE;
header('Content-Type: text/plain; charset=utf-8');

Debugger::enable();

register_shutdown_function(function() {
	Assert::match('
Strict Standards: mktime(): You should be using the time() function instead in %a% on line %d%

Deprecated: mktime(): The is_dst parameter is deprecated in %a% on line %d%

Notice: Undefined variable: x in %a% on line %d%

Warning: %a% in %a% on line %d%
', ob_get_clean());
});
ob_start();


@mktime(); // E_STRICT
@mktime(0, 0, 0, 1, 23, 1978, 1); // E_DEPRECATED
@$x++; // E_NOTICE
@min(1); // E_WARNING
@require 'E_COMPILE_WARNING.inc'; // E_COMPILE_WARNING (not working)
