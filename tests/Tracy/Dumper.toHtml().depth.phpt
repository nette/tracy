<?php

/**
 * Test: Tracy\Dumper::toHtml() depth & truncate
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


$arr = [
	'long' => str_repeat('Nette Framework', 1000),

	[
		[
			['hello' => 'world'],
		],
	],

	'long2' => str_repeat('Nette Framework', 1000),

	(object) [
		(object) [
			(object) ['hello' => 'world'],
		],
	],
];


Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-string">'long'</span> => <span class="tracy-dump-string" title="15000 characters">'Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework <span>…</span> '</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-string">'hello'</span> => <span class="tracy-dump-string" title="5 characters">'world'</span>
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-string">'long2'</span> => <span class="tracy-dump-string" title="15000 characters">'Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework <span>…</span> '</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-dynamic">hello</span>: <span class="tracy-dump-string" title="5 characters">'world'</span>
</div></div></div></div></pre>
XX
, Dumper::toHtml($arr));


Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-string">'long'</span> => <span class="tracy-dump-string" title="15000 characters">'Nette FrameworkNette FrameworkNette FrameworkNette <span>…</span> '</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-array">array</span> (1) …
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-string">'long2'</span> => <span class="tracy-dump-string" title="15000 characters">'Nette FrameworkNette FrameworkNette FrameworkNette <span>…</span> '</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span> …
</div></div></pre>
XX
, Dumper::toHtml($arr, [Dumper::DEPTH => 2, Dumper::TRUNCATE => 50]));


Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-dump-string" title="150 characters">'Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework'</span></pre>
XX
, Dumper::toHtml(str_repeat('Nette Framework', 10), [Dumper::TRUNCATE => 50]));


$arr = [1, 2, 3, 4];

Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-number">4</span>
</div></pre>
XX
, Dumper::toHtml($arr, [Dumper::ITEMS => 2]));


Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">1</span>: <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">2</span>: <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">3</span>: <span class="tracy-dump-number">4</span>
</div></pre>
XX
, Dumper::toHtml((object) $arr, [Dumper::ITEMS => 2]));


Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span>…
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">1</span>: <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span>…
</div></div></pre>
XX
, Dumper::toHtml([$arr, (object) $arr], [Dumper::ITEMS => 2]));
