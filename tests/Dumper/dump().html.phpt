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


test('html mode', function () {
	setHtmlMode();
	ob_start();
	dump(123);
	Assert::match(
		<<<'XX'
			<tracy-div><style>%a%</style>
			<script>%a%</script>
			<pre class="tracy-dump tracy-light"
			><a href="editor://%a%" class="tracy-dump-location" title="in file %a% on line %d%&#10;Click to open in editor">dump(123) 📍</a
			><span class="tracy-dump-number">123</span></pre>
			</tracy-div>
			XX,
		ob_get_clean(),
	);
});


test('dark theme', function () {
	Debugger::$dumpTheme = 'dark';

	ob_start();
	dump(123);
	Assert::match(
		<<<'XX'
			<tracy-div><pre class="tracy-dump tracy-dark"
			><a href="editor://%a%" class="tracy-dump-location" title="in file %a% on line %d%&#10;Click to open in editor">dump(123) 📍</a
			><span class="tracy-dump-number">123</span></pre>
			</tracy-div>
			XX,
		ob_get_clean(),
	);
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
	Assert::match("%A%'sensitive data'%A%", ob_get_clean());
});


test('returned value', function () {
	$obj = new stdClass;
	Assert::same(dump($obj), $obj);
});


test('multiple value', function () {
	$obj = new stdClass;
	ob_start();
	Assert::same(dump($obj, 123), $obj);
	Assert::match('%A%stdClass%A%123%A%', ob_get_clean());
});
