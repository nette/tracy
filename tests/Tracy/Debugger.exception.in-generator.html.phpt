<?php

/**
 * Test: Tracy\Debugger exception in generator in HTML.
 * @phpVersion 8.1  ReflectionGenerator::getTrace() is empty in PHP < 8.1
 * @httpCode   500
 * @exitCode   255
 * @outputMatchFile expected/Debugger.exception.in-generator.html.expect
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


$generator = (function (): iterable {
	yield 5;
	throw new Exception('The my exception', 123);
})();
$fn = function ($generator)  {
	foreach ($generator as $value) {
	}
};

$fn($generator);
