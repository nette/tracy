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
<style>%a%</style>
<script>%a%</script>
<pre class="tracy-dump"
><a href="editor://%a%" class="tracy-dump-location" title="in file %a% on line %d%&#10;Click to open in editor">Dumper::dump(123)) 📍</a
><span class="tracy-dump-number">123</span></pre>
XX
, ob_get_clean());
});


test(function () { // repeated html mode
	ob_start();
	Assert::same(123, Dumper::dump(123));
	Assert::match(<<<'XX'
<pre class="tracy-dump"
><a %A%>Dumper::dump(123)) 📍</a
><span class="tracy-dump-number">123</span></pre>
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


test(function () { // options
	$arr = ['loooooooooooooooooooooong texxxxxt', [2, 3, 4, 5, 6, 7, 8]];
	ob_start();
	Dumper::$showLocation = false;
	Dumper::$maxItems = 3;
	Dumper::dump($arr, [Dumper::TRUNCATE => 10]);
	Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='[]'
><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-string" title="34 characters">'looooooooo <span>…</span> g texxxxxt'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"array":null,"length":7,"items":[[0,2],[1,3],[2,4]]}'><span class="tracy-dump-array">array</span> (7)</span>
</div></pre>
XX
, ob_get_clean());
});
