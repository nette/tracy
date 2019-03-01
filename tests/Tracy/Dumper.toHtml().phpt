<?php

/**
 * Test: Tracy\Dumper::toHtml()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


// scalars & empty array
Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span>
</pre>', Dumper::toHtml(null));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-bool">true</span>
</pre>', Dumper::toHtml(true));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-bool">false</span>
</pre>', Dumper::toHtml(false));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0</span>
</pre>', Dumper::toHtml(0));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">1</span>
</pre>', Dumper::toHtml(1));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0.0</span>
</pre>', Dumper::toHtml(0.0));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0.1</span>
</pre>', Dumper::toHtml(0.1));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">INF</span>
</pre>', Dumper::toHtml(INF));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">-INF</span>
</pre>', Dumper::toHtml(-INF));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">NAN</span>
</pre>', Dumper::toHtml(NAN));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-string">""</span>
</pre>', Dumper::toHtml(''));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-string">"0"</span>
</pre>', Dumper::toHtml('0'));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-string">"\\x00"</span>
</pre>', Dumper::toHtml("\x00"));


// array (with snapshot)
Assert::match('<pre class="tracy-dump" data-tracy-snapshot=\'[]\'><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (5)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">2</span> => <span class="tracy-dump-array">array</span> ()
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">3</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-number">2</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">4</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'[[1,1],[2,2],[3,3],[4,4],[5,5],[6,6],[7,7]]\'><span class="tracy-dump-array">array</span> (7)</span>
</div></pre>', Dumper::toHtml([1, 'hello', [], [1, 2], [1 => 1, 2, 3, 4, 5, 6, 7]]));


// resource
Assert::match('<pre class="tracy-dump"><span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">#%d%</span></span>
<div class="tracy-collapsed">%A%', Dumper::toHtml(fopen(__FILE__, 'r')));


// object
Assert::match('<pre class="tracy-dump"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%a%</span>
</pre>', Dumper::toHtml(new stdClass));

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">10</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-null">null</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> <span class="tracy-dump-visibility">private</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> <span class="tracy-dump-visibility">protected</span> => <span class="tracy-dump-number">30.0</span>
</div></pre>', Dumper::toHtml(new Test));


// collapse (with snapshot)
Assert::match('<pre class="tracy-dump" data-tracy-snapshot=\'[]\'><span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'[[0,10],[1,null]]\'><span class="tracy-dump-array">array</span> (2)</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> <span class="tracy-dump-visibility">private</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> <span class="tracy-dump-visibility">protected</span> => <span class="tracy-dump-number">30.0</span>
</div></pre>', Dumper::toHtml(new Test, [Dumper::COLLAPSE_COUNT => 1]));

Assert::match('<pre class="tracy-dump" data-tracy-snapshot=\'[]\'><span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'[[0,10],[1,null]]\'><span class="tracy-dump-array">array</span> (2)</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> <span class="tracy-dump-visibility">private</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> <span class="tracy-dump-visibility">protected</span> => <span class="tracy-dump-number">30.0</span>
</div></pre>', Dumper::toHtml(new Test, [Dumper::COLLAPSE_COUNT => 1, Dumper::COLLAPSE => false]));

Assert::match('<pre class="tracy-dump" data-tracy-snapshot=\'{"1":{"name":"Test","hash":"%a%","items":[["x",[[0,10],[1,null]],0],["y","hello",2],["z",{"number":"30.0"},1]]}}\'><span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'{"object":1}\'><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
</pre>', Dumper::toHtml(new Test, [Dumper::COLLAPSE => true]));

Assert::match('<pre class="tracy-dump" data-tracy-snapshot=\'{"1":{"name":"Test","hash":"%a%","items":[["x",[[0,10],[1,null]],0],["y","hello",2],["z",{"number":"30.0"},1]]}}\'><span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'{"object":1}\'><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
</pre>', Dumper::toHtml(new Test, [Dumper::COLLAPSE => 3]));


// specials: closure, SplFileInfo, new class
Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Closure</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">file</span> => <span class="tracy-dump-string">"%a%"</span> (%i%)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">line</span> => <span class="tracy-dump-number">%i%</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">variables</span> => <span class="tracy-dump-array">array</span> ()
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">parameters</span> => <span class="tracy-dump-string">""</span>
</div></pre>', Dumper::toHtml(function () {}));

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">SplFileInfo</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">path</span> => <span class="tracy-dump-string">"%a%"</span> (%d%)
</div></pre>', Dumper::toHtml(new SplFileInfo(__FILE__)));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-object">class@anonymous</span> <span class="tracy-dump-hash">#%a%</span>
</pre>', Dumper::toHtml(new class {
}));


// recursion (with snapshot)
$arr = [1, 2, 3, ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']];
$arr[3][] = &$arr;
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'[]\'><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">2</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">3</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,[[0,1],[1,2],[2,3],[3,[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,[null]]]]]]]\'><span class="tracy-dump-array">array</span> (9)</span>
</div></pre>',
	Dumper::toHtml($arr)
);

$obj = new stdClass;
$obj->items = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', $obj];
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"1":{"name":"stdClass","hash":"%h%","items":[["items",[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,{"object":1}]],0]]}}\'><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%h%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">items</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,{"object":1}]]\'><span class="tracy-dump-array">array</span> (9)</span>
</div></pre>',
	Dumper::toHtml($obj)
);


// max depth  (with snapshot)
$arr = [1, [2, ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', [3, [4, [5, [6]]]]]], 3];
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'[]\'><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (3)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,[[0,3],[1,[null]]]]]\'><span class="tracy-dump-array">array</span> (9)</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">2</span> => <span class="tracy-dump-number">3</span>
</div></pre>',
	Dumper::toHtml($arr)
);

$obj = new stdClass;
$obj->items = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
$obj->items['x'] = new stdClass;
$obj->items['x']->b = new stdClass;
$obj->items['x']->b->c = new stdClass;
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"1":{"name":"stdClass","hash":"%h%","items":[["b",{"object":2},0]]},"2":{"name":"stdClass","hash":"%h%","items":[["c",{"object":3},0]]},"3":{"name":"stdClass","hash":"%h%"}}\'><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%h%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">items</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],["x",{"object":1}]]\'><span class="tracy-dump-array">array</span> (9)</span>
</div></pre>',
	Dumper::toHtml($obj)
);
