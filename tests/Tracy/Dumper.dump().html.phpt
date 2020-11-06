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


test(function () { // html mode
	header('Content-Type: text/html');
	ob_start();
	Assert::same(123, Dumper::dump(123));
	Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-dump-number">123</span></pre>
XX
, ob_get_clean());
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
	Assert::match("%A%'sensitive data'%A%", ob_get_clean());
});


test(function () { // returned value
	$obj = new stdClass;
	Assert::same(Dumper::dump($obj), $obj);
});
