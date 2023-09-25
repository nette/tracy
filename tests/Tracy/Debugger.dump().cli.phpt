<?php

/**
 * Test: Tracy\Debugger::dump() in CLI
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI !== 'cli') {
	Tester\Environment::skip('Requires CLI mode');
}


test('production mode', function () {
	Debugger::$productionMode = true;

	ob_start();
	Debugger::dump('sensitive data');
	Assert::same('', ob_get_clean());

	Assert::match("'forced'", Debugger::dump('forced', return: true));
});


test('development mode', function () {
	Debugger::$productionMode = false;

	ob_start();
	Debugger::dump('sensitive data');
	Assert::match('', ob_get_clean()); // is dumped to stout

	Assert::match("'forced'", Debugger::dump('forced', return: true));
});


test('returned value', function () {
	$obj = new stdClass;
	Assert::same(Debugger::dump($obj), $obj);
});
