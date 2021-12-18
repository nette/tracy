<?php

/**
 * Test: Tracy\Dumper::toHtml() locale
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


setlocale(LC_ALL, 'czech');

Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">-10.0</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-number">10.3</span>
		</div></pre>
		XX,
	Dumper::toHtml([-10.0, 10.3]),
);
