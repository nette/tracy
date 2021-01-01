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
$options = [Dumper::SNAPSHOT => &$snapshot, Dumper::THEME => false];

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span></pre>', Dumper::toHtml(null, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-bool">true</span></pre>', Dumper::toHtml(true, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0</span></pre>', Dumper::toHtml(0, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-array">array</span> (0)</pre>', Dumper::toHtml([], $options));
Assert::same([], $snapshot[0]);


// snapshot dump of array
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-dump='[[0,null],[1,true],[2,false],[3,0],[4,{"number":"0.0"}],[5,"string"],[6,{"string":"\u0027\u0026amp;\"","length":3}],[7,{"string":"<i>\\x00</i>","length":1}],[8,{"number":"INF"}],[9,{"number":"-INF"}],[10,{"number":"NAN"}]]'></pre>
XX
, Dumper::toHtml([null, true, false, 0, 0.0, 'string', "'&\"", "\x00", INF, -INF, NAN], $options));


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
Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span></pre>', Dumper::toHtml(null, $options));


// snapshot and resource
$snapshot = [];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"ref":"r%d%"}\'></pre>',
	Dumper::toHtml(fopen(__FILE__, 'r'), $options)
);
Assert::count(1, $snapshot[0]);


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
			['y', 'hello', 'Test'],
			['z', ['number' => '30.0'], 1],
		],
	],
], array_values(formatSnapshot($snapshot)));


// snapshot & location
$snapshot = [];
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-dump='{"ref":%d%}'
><a href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" class="tracy-dump-location" title="in file %a% on line %d%&#10;Click to open in editor">Dumper::toHtml(new Test, $options + ['location' => <span>…</span> N_CLASS])) 📍</a
></pre>
XX
, Dumper::toHtml(new Test, $options + ['location' => Dumper::LOCATION_SOURCE | Dumper::LOCATION_CLASS]));

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
			['y', 'hello', 'Test'],
			['z', ['number' => '30.0'], 1],
		],
	],
], array_values(formatSnapshot($snapshot)));


// snapshot & recursion
$snapshot = [];
$arr = [1, 2, 3];
$arr[] = &$arr;
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,1],[1,2],[2,3],[3,{"ref":"p1"},1]]\'></pre>',
	Dumper::toHtml($arr, $options)
);
Assert::equal([
	[
		'array' => null,
		'items' => [[0, 1], [1, 2], [2, 3], [3, ['ref' => 'p1'], 1]],
	],
], array_values(formatSnapshot($snapshot)));


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
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,1],[1,[[0,2],[1,[[0,3],[1,[[0,4],[1,{"array":null,"length":2}]]]]]]],[2,3]]\'></pre>',
	Dumper::toHtml($arr, $options + [Dumper::DEPTH => 4])
);
Assert::same([], $snapshot[0]);


$arr = [1, [2, [3, [4, []]]], 3];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,1],[1,[[0,2],[1,[[0,3],[1,[[0,4],[1,[]]]]]]]],[2,3]]\'></pre>',
	Dumper::toHtml($arr, $options + [Dumper::DEPTH => 4])
);
Assert::same([], $snapshot[0]);


$obj = new stdClass;
$obj->a = new stdClass;
$obj->a->b = new stdClass;
$obj->a->b->c = new stdClass;
$obj->a->b->c->d = new stdClass;
$obj->a->b->c->d->e = new stdClass;
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"ref":%d%}\'></pre>',
	Dumper::toHtml($obj, $options + [Dumper::DEPTH => 4])
);


// snapshot & remain references
$a = ['a'];
$b = ['b'];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,{"ref":"p1"},1],[1,{"ref":"p2"},2]]\'></pre>',
	Dumper::toHtml([&$a, &$b], $options)
);

Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,{"ref":"p2"},2],[1,{"ref":"p1"},1]]\'></pre>',
	Dumper::toHtml([&$b, &$a], $options)
);
