<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger exception in production mode includes agent hint.
 * @httpCode   500
 * @exitCode   255
 * @outputMatch %A%navigator.webdriver%A%console.error('Tracy: Server Error 500. Details have been logged on the server.')%A%
 */

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Error page is not rendered in CLI mode');
}


Debugger::$productionMode = true;
setHtmlMode();

Debugger::enable(Debugger::Production, getTempDir());

throw new Exception('The my exception', 123);
