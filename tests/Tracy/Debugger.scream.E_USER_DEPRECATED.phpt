<?php

/**
 * Test: Tracy\Debugger scream mode with specified severity.
 * @outputMatchFile expected/Debugger.scream.E_USER_DEPRECATED.expect
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
Debugger::$scream = E_USER_DEPRECATED;
header('Content-Type: text/plain; charset=utf-8');

Debugger::enable();

trigger_error('E_USER_WARNING that should be reported', E_USER_WARNING);
@trigger_error('Muted E_USER_WARNING that should be ignored', E_USER_WARNING);

@trigger_error('Muted E_USER_DEPRECATED that should be reported', E_USER_DEPRECATED);
