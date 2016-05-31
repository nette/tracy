<?php

/**
 * Test: Tracy\Debugger eval error in HTML.
 * @httpCode   500
 * @exitCode   255
 * @outputMatchFile Debugger.error-in-eval.expect
 */

use Tracy\Debugger;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bluescreen is not rendered in CLI mode');
}


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

function first($user, $pass)
{
	eval('trigger_error("The my error", E_USER_ERROR);');
}


echo @$undefined;
first('root', 'xxx');
