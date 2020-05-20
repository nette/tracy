<?php

/**
 * Test: dump() in HTML
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Requires CGI mode');
}


test(function () { // html mode
	header('Content-Type: text/html');
	ob_start();
	dump(123);
	Assert::match(<<<'XX'
<style>%a%</style>
<script>%a%</script>
<pre class="tracy-dump tracy-light"
><a href="editor://%a%" class="tracy-dump-location" title="in file %a% on line %d%&#10;Click to open in editor">dump(123) 📍</a
><span class="tracy-dump-number">123</span></pre>
XX
, ob_get_clean());
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
	Assert::match("%A%'sensitive data'%A%", ob_get_clean());
});


test(function () { // returned value
	$obj = new stdClass;
	Assert::same(dump($obj), $obj);
});


test(function () { // multiple value
	$obj = new stdClass;
	ob_start();
	Assert::same(dump($obj, 123), $obj);
	Assert::match('%A%stdClass%A%123%A%', ob_get_clean());
});
