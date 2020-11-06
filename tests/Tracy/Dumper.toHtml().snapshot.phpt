<?php

/**
 * Test: Tracy\Dumper::toHtml() snapshop
 */

declare(strict_types=1);

use Tester\Assert;
use Tester\Expect;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


function formatSnapshot(array $snapshot): array
{
	return json_decode(explode("'", Dumper::formatSnapshotAttribute($snapshot))[1], true);
}


// snapshot dump of scalars & empty array
$snapshot = [];
$options = [Dumper::SNAPSHOT => &$snapshot];

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span>
</pre>', Dumper::toHtml(null, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-bool">true</span>
</pre>', Dumper::toHtml(true, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0</span>
</pre>', Dumper::toHtml(0, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-array">array</span> ()
</pre>', Dumper::toHtml([], $options));
Assert::same([], $snapshot);


// snapshot dump of array
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,null],[1,true],[2,false],[3,0],[4,{"number":"0.0"}],[5,"string"],[6,"\u0027\u0026\""],[7,"\\\\x00"],[8,{"type":"INF"}],[9,{"type":"-INF"}],[10,{"type":"NAN"}]]\'></pre>',
	Dumper::toHtml([null, true, false, 0, 0.0, 'string', "'&\"", "\x00", INF, -INF, NAN], $options)
);


// snapshot dump of object
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"ref":%d%}\'></pre>',
	Dumper::toHtml(new stdClass, $options)
);

// twice with different identity
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"ref":%d%}\'></pre>',
	Dumper::toHtml(new stdClass, $options) // different object
);
Assert::equal([
	['object' => 'stdClass', 'items' => []],
	['object' => 'stdClass', 'items' => []],
], array_values(formatSnapshot($snapshot)));


// dump() with already created snapshot
Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span>
</pre>', Dumper::toHtml(null, $options));


// snapshot and resource
$snapshot = [];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"ref":"r%d%"}\'></pre>',
	Dumper::toHtml(fopen(__FILE__, 'r'), $options)
);
Assert::count(1, $snapshot);


// snapshot and collapse
$snapshot = [];
Assert::match(
	'<pre class="tracy-dump tracy-collapsed" data-tracy-dump=\'{"ref":%d%}\'></pre>',
	Dumper::toHtml(new Test, $options + [Dumper::COLLAPSE => true])
);


// snapshot content check
$snapshot = [];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"ref":%d%}\'></pre>',
	Dumper::toHtml(new Test, $options)
);

Assert::equal([
	[
		'object' => 'Test',
		'items' => [
			['x', [[0, 10], [1, null]], 0],
			['y', 'hello', 2],
			['z', ['number' => '30.0'], 1],
		],
	],
], array_values(formatSnapshot($snapshot)));


// snapshot & location
$snapshot = [];
Assert::match(
	'<pre class="tracy-dump" title="Dumper::toHtml(new Test, $options + [&#039;location&#039; =&gt; Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS])
in file %a% on line %d%" data-tracy-href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" data-tracy-dump=\'{"ref":%d%}\'><small>in <a href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" title="%a%:%d%">%a%:%d%</a></small></pre>',
	Dumper::toHtml(new Test, $options + ['location' => Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS])
);

Assert::equal([
	[
		'object' => 'Test',
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
], array_values(formatSnapshot($snapshot)));


// snapshot & recursion
$snapshot = [];
$arr = [1, 2, 3];
$arr[] = &$arr;
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,1],[1,2],[2,3],[3,[[0,1],[1,2],[2,3],[3,{"stop":[4,true]}]]]]\'></pre>',
	Dumper::toHtml($arr, $options)
);
Assert::same([], $snapshot);

$obj = new stdClass;
$obj->x = $obj;
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"ref":%d%}\'></pre>',
	Dumper::toHtml($obj, $options)
);


// snapshot & max depth
$snapshot = [];
$arr = [1, [2, [3, [4, [5, [6]]]]], 3];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,1],[1,[[0,2],[1,[[0,3],[1,[[0,4],[1,{"stop":[2,false]}]]]]]]],[2,3]]\'></pre>',
	Dumper::toHtml($arr, $options)
);
Assert::same([], $snapshot);


$arr = [1, [2, [3, [4, []]]], 3];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,1],[1,[[0,2],[1,[[0,3],[1,[[0,4],[1,[]]]]]]]],[2,3]]\'></pre>',
	Dumper::toHtml($arr, $options)
);
Assert::same([], $snapshot);


$obj = new stdClass;
$obj->a = new stdClass;
$obj->a->b = new stdClass;
$obj->a->b->c = new stdClass;
$obj->a->b->c->d = new stdClass;
$obj->a->b->c->d->e = new stdClass;
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"ref":%d%}\'></pre>',
	Dumper::toHtml($obj, $options)
);
