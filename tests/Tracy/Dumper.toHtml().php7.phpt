<?php

/**
 * Test: Tracy\Dumper::toHtml()
 * @phpversion 7
 */

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-object">class@anonymous</span> <span class="tracy-dump-hash">#%a%</span>
</pre>', Dumper::toHtml(new class {
}));
