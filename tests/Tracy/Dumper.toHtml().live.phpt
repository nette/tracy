<?php

/**
 * Test: Tracy\Dumper::toHtml() live
 */

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


$options = [Dumper::LIVE => true];

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span>
</pre>', Dumper::toHtml(null, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-bool">true</span>
</pre>', Dumper::toHtml(true, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0</span>
</pre>', Dumper::toHtml(0, $options));

Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,null],[1,true],[2,false],[3,0],[4,{"number":"0.0"}],[5,"string"],[6,"\u0027\u0026\""],[7,"\\\\x00"],[8,{"type":"INF"}],[9,{"type":"-INF"}],[10,{"type":"NAN"}]]\'></pre>',
	Dumper::toHtml([null, true, false, 0, 0.0, 'string', "'&\"", "\x00", INF, -INF, NAN], $options)
);

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-array">array</span> ()
</pre>', Dumper::toHtml([], $options));
Assert::same([], Dumper::fetchLiveData());


Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":"01"}\'></pre>',
	Dumper::toHtml(new stdClass, $options)
);

Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":"02"}\'></pre>',
	Dumper::toHtml(new stdClass, $options) // different object
);
Assert::same([
	'01' => ['name' => 'stdClass', 'editor' => null, 'items' => []],
	'02' => ['name' => 'stdClass', 'editor' => null, 'items' => []],
], Dumper::fetchLiveData());


Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"resource":"%d%"}\'></pre>',
	Dumper::toHtml(fopen(__FILE__, 'r'), $options)
);
Assert::count(1, Dumper::fetchLiveData());


Assert::match(
	'<pre class="tracy-dump tracy-collapsed" data-tracy-dump=\'{"object":"03"}\'></pre>',
	Dumper::toHtml(new Test, $options + [Dumper::COLLAPSE => true])
);
Assert::same([
	'03' => [
		'name' => 'Test',
		'editor' => null,
		'items' => [
			['x', [[0, 10], [1, null]], 0],
			['y', 'hello', 2],
			['z', ['number' => '30.0'], 1],
		],
	],
], Dumper::fetchLiveData());


Assert::match(
	'<pre class="tracy-dump" title="Dumper::toHtml(new Test, $options + [&#039;location&#039; =&gt; Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS])
in file %a% on line %d%" data-tracy-href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" data-tracy-dump=\'{"object":"04"}\'><small>in <a href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" title="%a%:%d%">%a%:%d%</a></small></pre>',
	Dumper::toHtml(new Test, $options + ['location' => Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS])
);

$data = Dumper::fetchLiveData();
Assert::type('int', $data['04']['editor']['line']);
Assert::type('string', $data['04']['editor']['url']);
unset($data['04']['editor']['line'], $data['04']['editor']['url']);

Assert::same([
	'04' => [
		'name' => 'Test',
		'editor' => [
			'file' => __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'DumpClass.php',
		],
		'items' => [
			['x', [[0, 10], [1, null]], 0],
			['y', 'hello', 2],
			['z', ['number' => '30.0'], 1],
		],
	],
], $data);
