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
	'<pre class="tracy-dump" data-tracy-snapshot=\'[]\' data-tracy-dump=\'[[0,null],[1,true],[2,false],[3,0],[4,{"number":"0.0"}],[5,"string"],[6,"\u0027\u0026\""],[7,{"string":"\\\\x00","length":1}],[8,{"number":"INF"}],[9,{"number":"-INF"}],[10,{"number":"NAN"}]]\'></pre>',
	Dumper::toHtml([null, true, false, 0, 0.0, 'string', "'&\"", "\x00", INF, -INF, NAN], $options)
);


// live dump of object
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"%d%":{"name":"stdClass","items":[]}}\' data-tracy-dump=\'{"object":%d%}\'></pre>',
	Dumper::toHtml(new stdClass, $options)
);

// twice with different identity
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"%d%":{"name":"stdClass","items":[]}}\' data-tracy-dump=\'{"object":%d%}\'></pre>',
	Dumper::toHtml(new stdClass, $options) // different object
);


// lazy dump and resource
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"r%d%":{"name":"stream resource","items":[%a%]}}\' data-tracy-dump=\'{"resource":"r%d%"}\'></pre>',
	Dumper::toHtml(fopen(__FILE__, 'r'), $options)
);


// lazy dump and collapse
Assert::match(
	'<pre class="tracy-dump tracy-collapsed" data-tracy-snapshot=\'{"%d%":{"name":"Test","items":[["x",[[0,10],[1,null]],0],["y","hello",2],["z",{"number":"30.0"},1]]}}\' data-tracy-dump=\'{"object":%d%}\'></pre>',
	Dumper::toHtml(new Test, $options + [Dumper::COLLAPSE => true])
);


// lazy dump & location
Assert::match(
	'<pre class="tracy-dump" title="Dumper::toHtml(new Test, $options + [&#039;location&#039; =&gt; Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS])
in file %a% on line %d%" data-tracy-href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" data-tracy-snapshot=\'{"%d%":{"name":"Test","editor":{"file":"%a%","line":%d%,"url":"editor:\/\/open\/?file=%a%\u0026line=%d%\u0026search=\u0026replace="},"items":[["x",[[0,10],[1,null]],0],["y","hello",2],["z",{"number":"30.0"},1]]}}\' data-tracy-dump=\'{"object":%d%}\'><small>in <a href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" title="%a%:%d%">%a%</b>:%d%</a></small></pre>',
	Dumper::toHtml(new Test, $options + ['location' => Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS])
);


// lazy dump & recursion
$arr = [1, 2, 3];
$arr[] = &$arr;
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'[]\' data-tracy-dump=\'[[0,1],[1,2],[2,3],[3,[[0,1],[1,2],[2,3],[3,{"stop":[4,true]}]]]]\'></pre>',
	Dumper::toHtml($arr, $options)
);

$obj = new stdClass;
$obj->x = $obj;
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"%d%":{"name":"stdClass","items":[["x",{"object":%d%},0]]}}\' data-tracy-dump=\'{"object":%d%}\'></pre>',
	Dumper::toHtml($obj, $options)
);


// lazy dump & max depth
$arr = [1, [2, [3, [4, [5, [6]]]]], 3];
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'[]\' data-tracy-dump=\'[[0,1],[1,[[0,2],[1,[[0,3],[1,[[0,4],[1,{"stop":[2,false]}]]]]]]],[2,3]]\'></pre>',
	Dumper::toHtml($arr, $options)
);

$obj = new stdClass;
$obj->a = new stdClass;
$obj->a->b = new stdClass;
$obj->a->b->c = new stdClass;
$obj->a->b->c->d = new stdClass;
$obj->a->b->c->d->e = new stdClass;
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"%d%":{"name":"stdClass","items":[["a",{"object":%d%},0]]},"%d%":{"name":"stdClass","items":[["b",{"object":%d%},0]]},"%d%":{"name":"stdClass","items":[["c",{"object":%d%},0]]},"%d%":{"name":"stdClass","items":[["d",{"object":%d%},0]]},"%d%":{"name":"stdClass"}}\' data-tracy-dump=\'{"object":%d%}\'></pre>',
	Dumper::toHtml($obj, $options)
);
