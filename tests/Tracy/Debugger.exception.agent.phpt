<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger exception for AI agent.
 * @httpCode   500
 * @exitCode   255
 * @outputMatchFile expected/Debugger.exception.agent.expect
 */

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bluescreen is not rendered in CLI mode');
}


Debugger::$productionMode = false;
setHtmlMode();
$_COOKIE['tracy-webdriver'] = '1';

Debugger::enable();

throw new Exception('The my exception', 123);
