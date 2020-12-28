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
$options = [Dumper::LAZY => true, Dumper::THEME => false];

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span></pre>', Dumper::toHtml(null, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-bool">true</span></pre>', Dumper::toHtml(true, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0</span></pre>', Dumper::toHtml(0, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-array">array</span> (0)</pre>', Dumper::toHtml([], $options));


// lazy dump of array
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='[]' data-tracy-dump='[[0,null],[1,true],[2,false],[3,"string"],[4,{"string":"\u0027\u0026amp;\"","length":3}],[5,{"string":"<span>\\x00</span>","length":1}]]'></pre>
XX
, Dumper::toHtml([null, true, false, 'string', "'&\"", "\x00"], $options));


// lazy dump of numbers
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='[]' data-tracy-dump='[[0,0],[1,{"number":"0.0"}],[2,1],[3,{"number":"1.0"}],[4,{"number":"9007199254740999"}],[5,{"number":"-9007199254740999"}],[6,{"number":"INF"}],[7,{"number":"-INF"}],[8,{"number":"NAN"}]]'></pre>
XX
, Dumper::toHtml([0, 0.0, 1, 1.0, 9007199254740999, -9007199254740999, INF, -INF, NAN], $options));


// live dump of object
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"%d%":{"object":"stdClass","items":[]}}' data-tracy-dump='{"ref":%d%}'></pre>
XX
, Dumper::toHtml(new stdClass, $options));

// twice with different identity
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"%d%":{"object":"stdClass","items":[]}}' data-tracy-dump='{"ref":%d%}'></pre>
XX
, Dumper::toHtml(new stdClass, $options)); // different object


// lazy dump and resource
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"r%d%":{"resource":"stream resource","items":[%a%]}}' data-tracy-dump='{"ref":"r%d%"}'></pre>
XX
, Dumper::toHtml(fopen(__FILE__, 'r'), $options));


// lazy dump and collapse
Assert::match(<<<'XX'
<pre class="tracy-dump tracy-collapsed" data-tracy-snapshot='{"%d%":{"object":"Test","items":[["x",[[0,10],[1,null]],0],["y","hello","Test"],["z",{"number":"30.0"},1]]}}' data-tracy-dump='{"ref":%d%}'></pre>
XX
, Dumper::toHtml(new Test, $options + [Dumper::COLLAPSE => true]));


// lazy dump & location
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"%d%":{"object":"Test","editor":{"file":"%a%","line":%d%,"url":"editor://open/?file=%a%\u0026line=%d%\u0026search=\u0026replace="},"items":[["x",[[0,10],[1,null]],0],["y","hello","Test"],["z",{"number":"30.0"},1]]}}' data-tracy-dump='{"ref":%d%}'
><a href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" class="tracy-dump-location" title="in file %a% on line %d%&#10;Click to open in editor">Dumper::toHtml(new Test, $options + ['location' => <span>…</span> N_CLASS])) 📍</a
></pre>
XX
, Dumper::toHtml(new Test, $options + ['location' => Dumper::LOCATION_SOURCE | Dumper::LOCATION_CLASS]));


// lazy dump & recursion
$arr = [1, 2, 3];
$arr[] = &$arr;
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"p1":{"array":null,"items":[[0,1],[1,2],[2,3],[3,{"ref":"p1"},1]]}}' data-tracy-dump='[[0,1],[1,2],[2,3],[3,{"ref":"p1"},1]]'></pre>
XX
, Dumper::toHtml($arr, $options));

$obj = new stdClass;
$obj->x = $obj;
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"%d%":{"object":"stdClass","items":[["x",{"ref":%d%},3]]}}' data-tracy-dump='{"ref":%d%}'></pre>
XX
, Dumper::toHtml($obj, $options));


// lazy dump & max depth
$arr = [1, [2, [3, [4, [5, [6]]]]], 3];
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='[]' data-tracy-dump='[[0,1],[1,[[0,2],[1,[[0,3],[1,[[0,4],[1,{"array":null,"length":2}]]]]]]],[2,3]]'></pre>
XX
, Dumper::toHtml($arr, $options + [Dumper::DEPTH => 4]));

$obj = new stdClass;
$obj->a = new stdClass;
$obj->a->b = new stdClass;
$obj->a->b->c = new stdClass;
$obj->a->b->c->d = new stdClass;
$obj->a->b->c->d->e = new stdClass;
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"%d%":{"object":"stdClass","items":[["a",{"ref":%d%},3]]},"%d%":{"object":"stdClass","items":[["b",{"ref":%d%},3]]},"%d%":{"object":"stdClass","items":[["c",{"ref":%d%},3]]},"%d%":{"object":"stdClass","items":[["d",{"ref":%d%},3]]},"%d%":{"object":"stdClass"}}' data-tracy-dump='{"ref":%d%}'></pre>
XX
, Dumper::toHtml($obj, $options + [Dumper::DEPTH => 4]));


// lazy dump & max string length
$arr = [str_repeat('x', 80)];
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='[]' data-tracy-dump='[[0,{"string":"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx <span>…</span> xxxxxxxxxx","length":80}]]'></pre>
XX
, Dumper::toHtml($arr, $options + [Dumper::TRUNCATE => 50]));


// lazy dump & max items
$arr = [1, 2, 3, 4, 5, 6, 7, 8];
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"%d%":{"object":"stdClass","length":8,"items":[["0",1,3],["1",2,3],["2",3,3],["3",4,3],["4",5,3]]}}' data-tracy-dump='[[0,{"array":null,"length":8,"items":[[0,1],[1,2],[2,3],[3,4],[4,5]]}],[1,{"ref":%d%}]]'></pre>
XX
, Dumper::toHtml([$arr, (object) $arr], $options + [Dumper::ITEMS => 5]));
