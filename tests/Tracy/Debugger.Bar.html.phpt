<?php

/**
 * Test: Tracy\Debugger Bar in HTML.
 * @outputMatch %A%<!-- Tracy Debug Bar -->%A%
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


Debugger::$productionMode = false;
setHtmlMode();

Debugger::enable();
