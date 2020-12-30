<?php

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


class Test
{
	public $a = [1 => [2 => [3 => 'item']]];
}

$obj = new Test;
$obj2 = new Test;
$arr = [1 => [2 => [3 => 'item']]];
$file = fopen(__FILE__, 'r');

$var = [
	$obj2,
	'a' => (object) [
		'b' => [
			'c' => [$obj, new Test, &$arr, $arr, $file],
		],
		$obj,
		$obj2,
		new Test,
		&$arr,
		$arr,
		$file,
	],
	$obj,
	$obj2,
	new Test,
	&$arr,
	$arr,
	$file,
];

Assert::match(<<<'XX'
<pre class="tracy-dump tracy-light"
><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (8)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">a</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-array">array</span> (1) …
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>a<span>'</span></span> => <span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div class="tracy-collapsed"><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">b</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>c<span>'</span></span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (5)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span> <i>see below</i>
<span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span> …
<span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-hash">&%d%</span> <span class="tracy-dump-array">array</span> (1) <i>see below</i>
<span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-array">array</span> (1) …
<span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">4</span> => <span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">@%d%</span></span>
<div class="tracy-collapsed"><span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">timed_out</span>: <span class="tracy-dump-bool">false</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">blocked</span>: <span class="tracy-dump-bool">true</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">eof</span>: <span class="tracy-dump-bool">false</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">wrapper_type</span>: <span class="tracy-dump-string" title="9 characters"><span>'</span>plainfile<span>'</span></span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">stream_type</span>: <span class="tracy-dump-string" title="5 characters"><span>'</span>STDIO<span>'</span></span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">mode</span>: <span class="tracy-dump-string"><span>'</span>r<span>'</span></span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">unread_bytes</span>: <span class="tracy-dump-number">0</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">seekable</span>: <span class="tracy-dump-bool">true</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">uri</span>: <span class="tracy-dump-string" title="%d% characters"><span>'</span>%a%<span>'</span></span>
</div></div></div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span> <i>see below</i>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">1</span>: <span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span> <i>see above</i>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">2</span>: <span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-public">a</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-array">array</span> (1) …
</div></div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">3</span>: <span class="tracy-dump-hash">&1</span> <span class="tracy-dump-array">array</span> (1) <i>see below</i>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">4</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-array">array</span> (1) …
</div></div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">5</span>: <span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">@%d%</span> <i>see above</i>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">a</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-array">array</span> (1) …
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span> <i>see above</i>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">3</span> => <span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">a</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-array">array</span> (1) …
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">4</span> => <span class="tracy-dump-hash">&1</span> <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-string" title="4 characters"><span>'</span>item<span>'</span></span>
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">5</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-string" title="4 characters"><span>'</span>item<span>'</span></span>
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">6</span> => <span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">@%d%</span> <i>see above</i>
</div></pre>
XX
, Dumper::toHtml($var, [Dumper::DEPTH => 4, Dumper::LAZY => false]));


// no above or below in lazy mode
$var = [
	$obj2,
	'a' => (object) [
		'b' => [
			'c' => [$obj],
		],
	],
	$obj,
	$obj2,
];

Assert::match(<<<'XX'
<pre class="tracy-dump tracy-light" data-tracy-snapshot='{"%d%":{"object":"Test","items":[["a",[[1,[[2,{"array":null,"length":1}]]]],0]]},"%d%":{"object":"Test","items":[["a",[[1,[[2,{"array":null,"length":1}]]]],0]]}}'
><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">a</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-array">array</span> (1) …
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>a<span>'</span></span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">b</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>c<span>'</span></span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"ref":%d%}'><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">a</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-array">array</span> (1) …
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"ref":%d%}'><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
</div></pre>
XX
, Dumper::toHtml($var, [Dumper::DEPTH => 4]));
