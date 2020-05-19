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
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">long</span> => <span class="tracy-dump-string">'Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework … '</span> (15000)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-key">hello</span> => <span class="tracy-dump-string">'world'</span> (5)
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">long2</span> => <span class="tracy-dump-string">'Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework … '</span> (15000)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-dynamic">hello</span>: <span class="tracy-dump-string">'world'</span> (5)
</div></div></div></div></pre>
XX
, Dumper::toHtml($arr));


Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">long</span> => <span class="tracy-dump-string">'Nette FrameworkNette FrameworkNette FrameworkNette … '</span> (15000)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-array">array</span> (1) …
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">long2</span> => <span class="tracy-dump-string">'Nette FrameworkNette FrameworkNette FrameworkNette … '</span> (15000)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span> …
</div></div></pre>
XX
, Dumper::toHtml($arr, [Dumper::DEPTH => 2, Dumper::TRUNCATE => 50]));


$arr = [1, 2, 3, 4];

Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">2</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">3</span> => <span class="tracy-dump-number">4</span>
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
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span>…
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">1</span>: <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span>…
</div></div></pre>
XX
, Dumper::toHtml([$arr, (object) $arr], [Dumper::ITEMS => 2]));
