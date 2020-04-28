<?php

/**
 * Test: Tracy\Debugger::dump() production vs development
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Requires CGI mode');
}


header('Content-Type: text/plain');
Tracy\Dumper::$useColors = false;


test(function () { // production mode
	Debugger::$productionMode = true;

	ob_start();
	Debugger::dump('sensitive data');
	Assert::same('', ob_get_clean());

	Assert::match('%a%forced%a%', Debugger::dump('forced', true));
});


test(function () { // development mode
	Debugger::$productionMode = false;

	ob_start();
	Debugger::dump('sensitive data');
	Assert::match("'sensitive data'
	", ob_get_clean());

	Assert::match('%a%forced%a%', Debugger::dump('forced', true));
});


test(function () { // returned value
	$obj = new stdClass;
	Assert::same(Debugger::dump($obj), $obj);
});
