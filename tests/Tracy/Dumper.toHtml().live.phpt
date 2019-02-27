<?php

/**
 * Test: Tracy\Dumper::toHtml() live static snapshot
 */

declare(strict_types=1);

use Tester\Assert;
use Tester\Expect;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


function formatSnapshot(): array
{
	return json_decode(explode("'", Dumper::formatSnapshotAttribute(Dumper::$liveSnapshot))[1], true);
}


// live dump of scalars & empty array
$options = [Dumper::LIVE => true];

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span>
</pre>', Dumper::toHtml(null, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-bool">true</span>
</pre>', Dumper::toHtml(true, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0</span>
</pre>', Dumper::toHtml(0, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-array">array</span> ()
</pre>', Dumper::toHtml([], $options));
Assert::same([], Dumper::$liveSnapshot);


// live dump of array
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,null],[1,true],[2,false],[3,0],[4,{"number":"0.0"}],[5,"string"],[6,"\u0027\u0026\""],[7,"\\\\x00"],[8,{"type":"INF"}],[9,{"type":"-INF"}],[10,{"type":"NAN"}]]\'></pre>',
	Dumper::toHtml([null, true, false, 0, 0.0, 'string', "'&\"", "\x00", INF, -INF, NAN], $options)
);


// live dump of object
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":1}\'></pre>',
	Dumper::toHtml(new stdClass, $options)
);

// twice with different identity
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":2}\'></pre>',
	Dumper::toHtml(new stdClass, $options) // different object
);
Assert::equal([
	1 => ['name' => 'stdClass', 'hash' => Expect::match('%h%'), 'items' => []],
	2 => ['name' => 'stdClass', 'hash' => Expect::match('%h%'), 'items' => []],
], formatSnapshot());


// dump() with already created live snapshot
Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span>
</pre>', Dumper::toHtml(null, $options));


// live dump and resource
Dumper::$liveSnapshot = [];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"resource":%d%}\'></pre>',
	Dumper::toHtml(fopen(__FILE__, 'r'), $options)
);
Assert::count(1, Dumper::$liveSnapshot);


// live dump and collapse
Dumper::$liveSnapshot = [];
Assert::match(
	'<pre class="tracy-dump tracy-collapsed" data-tracy-dump=\'{"object":1}\'></pre>',
	Dumper::toHtml(new Test, $options + [Dumper::COLLAPSE => true])
);


// snapshot content check
Dumper::$liveSnapshot = [];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":1}\'></pre>',
	Dumper::toHtml(new Test, $options)
);

Assert::equal([
	1 => [
		'name' => 'Test',
		'hash' => Expect::match('%h%'),
		'items' => [
			['x', [[0, 10], [1, null]], 0],
			['y', 'hello', 2],
			['z', ['number' => '30.0'], 1],
		],
	],
], formatSnapshot());


// live dump & location
Dumper::$liveSnapshot = [];
Assert::match(
	'<pre class="tracy-dump" title="Dumper::toHtml(new Test, $options + [&#039;location&#039; =&gt; Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS])
in file %a% on line %d%" data-tracy-href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" data-tracy-dump=\'{"object":1}\'><small>in <a href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" title="%a%:%d%">%a%:%d%</a></small></pre>',
	Dumper::toHtml(new Test, $options + ['location' => Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS])
);

Assert::equal([
	1 => [
		'name' => 'Test',
		'hash' => Expect::match('%h%'),
		'editor' => [
			'file' => __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'DumpClass.php',
			'line' => Expect::type('int'),
			'url' => Expect::type('string'),
		],
		'items' => [
			['x', [[0, 10], [1, null]], 0],
			['y', 'hello', 2],
			['z', ['number' => '30.0'], 1],
		],
	],
], formatSnapshot());
