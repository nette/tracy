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


header('Content-Type: text/plain');


test(function () { // text mode
	ob_start();
	Dumper::dump(123);
	Assert::match('123', ob_get_clean());
});


test(function () { // production mode
	Debugger::$productionMode = true;

	ob_start();
	Dumper::dump('sensitive data');
	Assert::same('', ob_get_clean());
});


test(function () { // development mode
	Debugger::$productionMode = false;

	ob_start();
	Dumper::dump('sensitive data');
	Assert::match("'sensitive data'", ob_get_clean());
});
