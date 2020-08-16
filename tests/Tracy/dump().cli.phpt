<?php

/**
 * Test: dump() in CLI
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI !== 'cli') {
	Tester\Environment::skip('Requires CLI mode');
}


test('colors', function () {
	putenv('FORCE_COLOR=1');
	ob_start();
	dump(123);
	Assert::match("\e[1;32m123\e[0m", ob_get_clean());
});


test('no color', function () {
	Dumper::$terminalColors = null;
	ob_start();
	dump(123);
	Assert::match('123', ob_get_clean());
});


test('production mode', function () {
	Debugger::$productionMode = true;
	ob_start();
	dump('sensitive data');
	Assert::same('', ob_get_clean());
});


test('development mode', function () {
	Debugger::$productionMode = false;
	ob_start();
	dump('sensitive data');
	Assert::match("'sensitive data'", ob_get_clean());
});


test('returned value', function () {
	$obj = new stdClass;
	Assert::same(dump($obj), $obj);
});
