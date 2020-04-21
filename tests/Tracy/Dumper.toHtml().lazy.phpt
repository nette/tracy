<?php

/**
 * Test: Tracy\Dumper::toHtml() lazy => true
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


// lazy dump of scalars & empty array
$options = [Dumper::LAZY => true];

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span>
</pre>', Dumper::toHtml(null, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-bool">true</span>
</pre>', Dumper::toHtml(true, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0</span>
</pre>', Dumper::toHtml(0, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-array">array</span> ()
</pre>', Dumper::toHtml([], $options));


// lazy dump of array
Assert::match(
	<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='[]' data-tracy-dump='[[0,null],[1,true],[2,false],[3,0],[4,{"number":"0.0"}],[5,"string"],[6,{"string":"\u0027\u0026amp;\"","length":3}],[7,{"string":"\\x00","length":1}],[8,{"number":"INF"}],[9,{"number":"-INF"}],[10,{"number":"NAN"}]]'></pre>
XX
, Dumper::toHtml([null, true, false, 0, 0.0, 'string', "'&\"", "\x00", INF, -INF, NAN], $options));


// live dump of object
Assert::match(
	<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"%d%":{"name":"stdClass","items":[]}}' data-tracy-dump='{"object":%d%}'></pre>
XX
, Dumper::toHtml(new stdClass, $options));

// twice with different identity
Assert::match(
	<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"%d%":{"name":"stdClass","items":[]}}' data-tracy-dump='{"object":%d%}'></pre>
XX
, Dumper::toHtml(new stdClass, $options)); // different object


// lazy dump and resource
Assert::match(
	<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"r%d%":{"name":"stream resource","items":[%a%]}}' data-tracy-dump='{"resource":"r%d%"}'></pre>
XX
, Dumper::toHtml(fopen(__FILE__, 'r'), $options));


// lazy dump and collapse
Assert::match(
	<<<'XX'
<pre class="tracy-dump tracy-collapsed" data-tracy-snapshot='{"%d%":{"name":"Test","items":[["x",[[0,10],[1,null]],0],["y","hello","Test"],["z",{"number":"30.0"},1]]}}' data-tracy-dump='{"object":%d%}'></pre>
XX
, Dumper::toHtml(new Test, $options + [Dumper::COLLAPSE => true]));


// lazy dump & location
Assert::match(
	<<<'XX'
<pre class="tracy-dump" title="Dumper::toHtml(new Test, $options + [&apos;location&apos; =&gt; Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS]))
in file %a% on line %d%" data-tracy-href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" data-tracy-snapshot='{"%d%":{"name":"Test","editor":{"file":"%a%","line":%d%,"url":"editor:\/\/open\/?file=%a%\u0026line=%d%\u0026search=\u0026replace="},"items":[["x",[[0,10],[1,null]],0],["y","hello","Test"],["z",{"number":"30.0"},1]]}}' data-tracy-dump='{"object":%d%}'><small>in <a href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" title="%a%:%d%">%a%</b>:%d%</a></small></pre>
XX
, Dumper::toHtml(new Test, $options + ['location' => Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS]));


// lazy dump & recursion
$arr = [1, 2, 3];
$arr[] = &$arr;
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"a1":{"items":[[0,1],[1,2],[2,3],[3,{"array":"a1"},1]]}}\' data-tracy-dump=\'[[0,1],[1,2],[2,3],[3,{"array":"a1"},1]]\'></pre>',
	Dumper::toHtml($arr, $options)
);

$obj = new stdClass;
$obj->x = $obj;
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"%d%":{"name":"stdClass","items":[["x",{"object":%d%},3]]}}\' data-tracy-dump=\'{"object":%d%}\'></pre>',
	Dumper::toHtml($obj, $options)
);


// lazy dump & max depth
$arr = [1, [2, [3, [4, [5, [6]]]]], 3];
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'[]\' data-tracy-dump=\'[[0,1],[1,[[0,2],[1,[[0,3],[1,[[0,4],[1,{"stop":2}]]]]]]],[2,3]]\'></pre>',
	Dumper::toHtml($arr, $options)
);

$obj = new stdClass;
$obj->a = new stdClass;
$obj->a->b = new stdClass;
$obj->a->b->c = new stdClass;
$obj->a->b->c->d = new stdClass;
$obj->a->b->c->d->e = new stdClass;
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"%d%":{"name":"stdClass","items":[["a",{"object":%d%},3]]},"%d%":{"name":"stdClass","items":[["b",{"object":%d%},3]]},"%d%":{"name":"stdClass","items":[["c",{"object":%d%},3]]},"%d%":{"name":"stdClass","items":[["d",{"object":%d%},3]]},"%d%":{"name":"stdClass"}}\' data-tracy-dump=\'{"object":%d%}\'></pre>',
	Dumper::toHtml($obj, $options)
);


// lazy dump & max string length
$arr = [str_repeat('x', 80)];
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'[]\' data-tracy-dump=\'[[0,{"string":"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx … ","length":80}]]\'></pre>',
	Dumper::toHtml($arr, $options + [Dumper::TRUNCATE => 50])
);


// lazy dump & max items
$arr = [1, 2, 3, 4, 5, 6, 7, 8];
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"A0":{"length":8,"items":[[0,1],[1,2],[2,3],[3,4],[4,5]]},"%d%":{"name":"stdClass","length":8,"items":[["0",1,3],["1",2,3],["2",3,3],["3",4,3],["4",5,3]]}}\' data-tracy-dump=\'[[0,{"array":"A0"}],[1,{"object":%d%}]]\'></pre>',
	Dumper::toHtml([$arr, (object) $arr], $options + [Dumper::ITEMS => 5])
);
