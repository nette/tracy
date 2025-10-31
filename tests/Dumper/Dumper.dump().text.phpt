<?php

/**
 * Test: Tracy\Dumper::dump() in text
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Requires CGI mode');
}


Dumper::$terminalColors = null;


test('text mode', function () {
	ob_start();
	Dumper::dump(123);
	Assert::match('123', ob_get_clean());
});


test('production mode', function () {
	Debugger::$productionMode = true;

	ob_start();
	Dumper::dump('sensitive data');
	Assert::match("'sensitive data'", ob_get_clean());
});


test('development mode', function () {
	Debugger::$productionMode = false;

	ob_start();
	Dumper::dump('sensitive data');
	Assert::match("'sensitive data'", ob_get_clean());
});
