<?php

/**
 * Test: Tracy\Dumper::dump() in HTML
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Requires CGI mode');
}


test('html mode', function () {
	header('Content-Type: text/html');
	ob_start();
	Assert::same(123, Dumper::dump(123));
	Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-dump-number">123</span></pre>
XX
, ob_get_clean());
});


test('production mode', function () {
	Debugger::$productionMode = true;

	ob_start();
	Dumper::dump('sensitive data');
	Assert::match("%A%'sensitive data'%A%", ob_get_clean());
});


test('development mode', function () {
	Debugger::$productionMode = false;

	ob_start();
	Dumper::dump('sensitive data');
	Assert::match("%A%'sensitive data'%A%", ob_get_clean());
});


test('returned value', function () {
	$obj = new stdClass;
	Assert::same(Dumper::dump($obj), $obj);
});
