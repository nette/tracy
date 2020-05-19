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
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'[]\'><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (3)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,[[0,3],[1,{"array":null,"length":2}]]]]\'><span class="tracy-dump-array">array</span> (9)</span>
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
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"%d%":{"object":"stdClass","items":[["b",{"ref":%d%},3]]},"%d%":{"object":"stdClass","items":[["c",{"ref":%d%},3]]},"%d%":{"object":"stdClass"}}\'><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">items</span>: <span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],["x",{"ref":%d%}]]\'><span class="tracy-dump-array">array</span> (9)</span>
</div></pre>',
	Dumper::toHtml($obj)
);


// recursion
$arr = [1, 2, 3, ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']];
$arr[3][] = &$arr;
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"p1":{"array":null,"items":[[0,1],[1,2],[2,3],[3,[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,{"ref":"p1"},1]]]]}}\'><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">2</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">3</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,{"ref":"p1"},1]]\'><span class="tracy-dump-array">array</span> (9)</span>
</div></pre>',
	Dumper::toHtml($arr)
);

$obj = new stdClass;
$obj->items = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', $obj];
Assert::match(
	'<pre class="tracy-dump" data-tracy-snapshot=\'{"%d%":{"object":"stdClass","items":[["items",[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,{"ref":%d%}]],3]]}}\'><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">items</span>: <span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'[[0,"a"],[1,"b"],[2,"c"],[3,"d"],[4,"e"],[5,"f"],[6,"g"],[7,"h"],[8,{"ref":%d%}]]\'><span class="tracy-dump-array">array</span> (9)</span>
</div></pre>',
	Dumper::toHtml($obj)
);
