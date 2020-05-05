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


test(function () { // colors
	putenv('FORCE_COLOR=1');
	ob_start();
	dump(123);
	Assert::match("\e[1;32m123\e[0m", ob_get_clean());
});


test(function () { // no color
	Dumper::$terminalColors = null;
	ob_start();
	dump(123);
	Assert::match('123', ob_get_clean());
});


test(function () { // production mode
	Debugger::$productionMode = true;
	ob_start();
	dump('sensitive data');
	Assert::same('', ob_get_clean());
});


test(function () { // development mode
	Debugger::$productionMode = false;
	ob_start();
	dump('sensitive data');
	Assert::match("'sensitive data'", ob_get_clean());
});


test(function () { // returned value
	$obj = new stdClass;
	Assert::same(dump($obj), $obj);
});
