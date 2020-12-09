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


Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">long</span> => <span class="tracy-dump-string">"Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ... "</span> (15000)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-key">hello</span> => <span class="tracy-dump-string">"world"</span> (5)
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">long2</span> => <span class="tracy-dump-string">"Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ... "</span> (15000)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-key">hello</span> => <span class="tracy-dump-string">"world"</span> (5)
</div></div></div></div></pre>', Dumper::toHtml($arr));


Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">long</span> => <span class="tracy-dump-string">"Nette FrameworkNette FrameworkNette FrameworkNette ... "</span> (15000)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-array">array</span> (1) [ ... ]
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">long2</span> => <span class="tracy-dump-string">"Nette FrameworkNette FrameworkNette FrameworkNette ... "</span> (15000)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%a%</span> { ... }
</div></div></pre>', Dumper::toHtml($arr, [Dumper::DEPTH => 2, Dumper::TRUNCATE => 50]));
