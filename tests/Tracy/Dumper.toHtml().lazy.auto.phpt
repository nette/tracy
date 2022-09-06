<?php

/**
 * Test: Tracy\Dumper::toHtml() auto-lazy
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


// depth
$arr = [1, [2, ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', [3, [4, [5, [6]]]]]], 3];
Assert::match(<<<'XX'
<pre class="tracy-dump tracy-light" data-tracy-snapshot='[]'
><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (3)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump='[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,[[0,3],[1,{"array":null,"length":2}]]]]'><span class="tracy-dump-array">array</span> (9)</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-number">3</span>
</div></pre>
XX
	, Dumper::toHtml($arr, [Dumper::DEPTH => 4]));

$obj = new stdClass;
$obj->items = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
$obj->items['x'] = new stdClass;
$obj->items['x']->b = new stdClass;
$obj->items['x']->b->c = new stdClass;
Assert::match(<<<'XX'
<pre class="tracy-dump tracy-light" data-tracy-snapshot='{"%d%":{"object":"stdClass","items":[["b",{"ref":%d%},3]]},"%d%":{"object":"stdClass","items":[["c",{"ref":%d%},3]]},"%d%":{"object":"stdClass"}}'
><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">items</span>: <span class="tracy-toggle tracy-collapsed" data-tracy-dump='[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],["x",{"ref":%d%}]]'><span class="tracy-dump-array">array</span> (9)</span>
</div></pre>
XX
	, Dumper::toHtml($obj, [Dumper::DEPTH => 4]));


// recursion
$arr = [1, 2, 3, ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']];
$arr[3][] = &$arr;
Assert::match(<<<'XX'
<pre class="tracy-dump tracy-light" data-tracy-snapshot='{"p1":{"array":null,"items":[[0,1],[1,2],[2,3],[3,[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,{"ref":"p1"},1]]]]}}'
><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">3</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump='[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,{"ref":"p1"},1]]'><span class="tracy-dump-array">array</span> (9)</span>
</div></pre>
XX
	, Dumper::toHtml($arr));

$obj = new stdClass;
$obj->items = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', $obj];
Assert::match(<<<'XX'
<pre class="tracy-dump tracy-light" data-tracy-snapshot='{"%d%":{"object":"stdClass","items":[["items",[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,{"ref":%d%}]],3]]}}'
><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">items</span>: <span class="tracy-toggle tracy-collapsed" data-tracy-dump='[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,{"ref":%d%}]]'><span class="tracy-dump-array">array</span> (9)</span>
</div></pre>
XX
	, Dumper::toHtml($obj));

// recursion fix
$arr = [new stdClass, 'arr' => [1, 2, 3, 4, 5, 6]];
$obj = (object) $arr;
$obj->arr[] = $obj;

Assert::match(<<<'XX'
<pre class="tracy-dump tracy-light" data-tracy-snapshot='{"%d%":{"object":"stdClass","items":[["0",{"ref":%d%},3],["arr",[[0,1],[1,2],[2,3],[3,4],[4,5],[5,6],[6,{"ref":%d%}]],3]]},"%d%":{"object":"stdClass","items":[]}}'
><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">arr</span>: <span class="tracy-toggle tracy-collapsed" data-tracy-dump='[[0,1],[1,2],[2,3],[3,4],[4,5],[5,6],[6,{"ref":%d%}]]'><span class="tracy-dump-array">array</span> (7)</span>
</div></pre>
XX
	, Dumper::toHtml($obj));


// lazy dump & max items
$arr = [1, 2, 3, 4, 5, 6, 7, 8];
Assert::match(<<<'XX'
<pre class="tracy-dump tracy-light" data-tracy-snapshot='[]'
><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"array":null,"length":8,"items":[[0,1],[1,2],[2,3],[3,4],[4,5]]}'><span class="tracy-dump-array">array</span> (8)</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">1</span>: <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">2</span>: <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">3</span>: <span class="tracy-dump-number">4</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">4</span>: <span class="tracy-dump-number">5</span>
<span class="tracy-dump-indent">   |  </span>…
</div></div></pre>
XX
	, Dumper::toHtml([$arr, (object) $arr], [Dumper::ITEMS => 5]));


// lazy dump & max items & reference
$arr = [1, 2, 3, 4, 5, 6, 7, 8];
Assert::match(<<<'XX'
<pre class="tracy-dump tracy-light" data-tracy-snapshot='{"p1":{"array":null,"length":8,"items":[[0,1],[1,2],[2,3],[3,4],[4,5]]}}'
><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-hash">&1</span> <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"ref":"p1"}'><span class="tracy-dump-array">array</span> (8)</span>
</div></pre>
XX
	, Dumper::toHtml([&$arr], [Dumper::ITEMS => 5]));
