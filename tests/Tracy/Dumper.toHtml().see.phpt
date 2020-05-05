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
$arr = [1 => [2 => [3 => 'item']]];
$file = fopen(__FILE__, 'r');

$var = [
	'a' => (object) [
		'b' => [
			'c' => [$obj, new Test, &$arr, $arr, $file],
		],
		$obj,
		new Test,
		&$arr,
		$arr,
		$file,
	],
	$obj,
	new Test,
	&$arr,
	$arr,
	$file,
];


Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='{"%d%":{"name":"Test","items":[["a",[[1,[[2,{"stop":1}]]]],0]]},"a1":{"length":1,"items":[[1,[[2,[[3,"item"]]]]]]},"r%d%":{"name":"stream resource","items":[["timed_out",false],["blocked",true],["eof",false],["wrapper_type","plainfile"],["stream_type","STDIO"],["mode","r"],["unread_bytes",0],["seekable",true],["uri","%a%"]]}}'><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (6)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-string">'a'</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">b</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-string">'c'</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (5)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"object":%d%}'><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>

<span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span> …
<span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-hash">&1</span> <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"array":"a1"}'><span class="tracy-dump-array">array</span> (1)</span>

<span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-array">array</span> (1) …
<span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">4</span> => <span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">@%d%</span></span>
<div class="tracy-collapsed"><span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">timed_out</span>: <span class="tracy-dump-bool">false</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">blocked</span>: <span class="tracy-dump-bool">true</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">eof</span>: <span class="tracy-dump-bool">false</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">wrapper_type</span>: <span class="tracy-dump-string" title="9 characters">'plainfile'</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">stream_type</span>: <span class="tracy-dump-string" title="5 characters">'STDIO'</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">mode</span>: <span class="tracy-dump-string">'r'</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">unread_bytes</span>: <span class="tracy-dump-number">0</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">seekable</span>: <span class="tracy-dump-bool">true</span>
<span class="tracy-dump-indent">   |  |  |  |  </span><span class="tracy-dump-virtual">uri</span>: <span class="tracy-dump-string" title="%d% characters">'%a%'</span>
</div></div></div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"object":%d%}'><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>

<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">1</span>: <span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-public">a</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-array">array</span> (1) …
</div></div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">2</span>: <span class="tracy-dump-hash">&1</span> <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"array":"a1"}'><span class="tracy-dump-array">array</span> (1)</span>

<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">3</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-array">array</span> (1) …
</div></div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">4</span>: <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"resource":"r%d%"}'><span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">@%d%</span></span>

</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">a</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-array">array</span> (1) …
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">a</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-array">array</span> (1) …
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-hash">&1</span> <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-string" title="4 characters">'item'</span>
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">3</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-string" title="4 characters">'item'</span>
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">4</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"resource":"r%d%"}'><span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">@%d%</span></span>

</div></pre>
XX
, Dumper::toHtml($var, [Dumper::DEPTH => 4]));
