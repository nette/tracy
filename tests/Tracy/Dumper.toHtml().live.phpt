<?php

/**
 * Test: Tracy\Dumper::toHtml() live
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


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


// live dump of array
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'[]\' data-tracy-dump=\'[[0,null],[1,true],[2,false],[3,0],[4,{"number":"0.0"}],[5,"string"],[6,"\u0027\u0026\""],[7,"\\\\x00"],[8,{"type":"INF"}],[9,{"type":"-INF"}],[10,{"type":"NAN"}]]\'></pre>',
	Dumper::toHtml([null, true, false, 0, 0.0, 'string', "'&\"", "\x00", INF, -INF, NAN], $options)
);


// twice live dump
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"1":{"name":"stdClass","hash":"%h%","editor":null,"items":[]}}\' data-tracy-dump=\'{"object":1}\'></pre>',
	Dumper::toHtml(new stdClass, $options)
);

Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"1":{"name":"stdClass","hash":"%h%","editor":null,"items":[]}}\' data-tracy-dump=\'{"object":1}\'></pre>',
	Dumper::toHtml(new stdClass, $options) // different object
);


// live dump and resource
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"%d%":{"name":"stream resource","hash":%d%,"items":[%a%]}}\' data-tracy-dump=\'{"resource":%d%}\'></pre>',
	Dumper::toHtml(fopen(__FILE__, 'r'), $options)
);


// live dump and collapse
Assert::match(
	'<pre class="tracy-dump tracy-collapsed" data-tracy-snapshot=\'{"1":{"name":"Test","hash":"%h%","editor":null,"items":[["x",[[0,10],[1,null]],0],["y","hello",2],["z",{"number":"30.0"},1]]}}\' data-tracy-dump=\'{"object":1}\'></pre>',
	Dumper::toHtml(new Test, $options + [Dumper::COLLAPSE => true])
);


// live dump & location
Assert::match(
	'<pre class="tracy-dump" title="Dumper::toHtml(new Test, $options + [&#039;location&#039; =&gt; Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS])
in file %a% on line %d%" data-tracy-href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" data-tracy-snapshot=\'{"1":{"name":"Test","hash":"%h%","editor":{"file":"%a%","line":%d%,"url":"editor:\/\/open\/?file=%a%\u0026line=%d%\u0026search=\u0026replace="},"items":[["x",[[0,10],[1,null]],0],["y","hello",2],["z",{"number":"30.0"},1]]}}\' data-tracy-dump=\'{"object":1}\'><small>in <a href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" title="%a%:%d%">%a%</b>:%d%</a></small></pre>',
	Dumper::toHtml(new Test, $options + ['location' => Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS])
);
