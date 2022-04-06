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
	setHtmlMode();
	ob_start();
	Assert::same(123, Dumper::dump(123));
	Assert::match(<<<'XX'
<style>%a%</style>
<script>%a%</script>
<pre class="tracy-dump tracy-light"
><a href="editor://%a%" class="tracy-dump-location" title="in file %a% on line %d%&#10;Click to open in editor">Dumper::dump(123)) 📍</a
><span class="tracy-dump-number">123</span></pre>
XX
, ob_get_clean());
});


test('repeated html mode', function () {
	ob_start();
	Assert::same(123, Dumper::dump(123));
	Assert::match(<<<'XX'
<pre class="tracy-dump tracy-light"
><a %A%>Dumper::dump(123)) 📍</a
><span class="tracy-dump-number">123</span></pre>
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
