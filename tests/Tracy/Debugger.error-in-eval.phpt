<?php

/**
 * Test: Tracy\Debugger eval error in HTML.
 * @httpCode   500
 * @exitCode   255
 * @outputMatchFile expected/Debugger.error-in-eval.expect
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bluescreen is not rendered in CLI mode');
}


Debugger::$productionMode = false;
setHtmlMode();

Debugger::enable();


function first($user, $pass)
{
	eval('trigger_error("The my error", E_USER_ERROR);');
}


first('root', 'xxx');
