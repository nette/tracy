<?php

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


Assert::match(
	'<pre class="tracy-dump tracy-light"><span class="tracy-dump-number">123</span></pre>',
	Dumper::toHtml(123, [Dumper::KEYS_TO_HIDE => ['password', 'pin']], 'pass'),
);

Assert::match(
	'<pre class="tracy-dump tracy-light"><span class="tracy-dump-virtual">***** (int)</span></pre>',
	Dumper::toHtml(123, [Dumper::KEYS_TO_HIDE => ['password', 'pin']], 'password'),
);
