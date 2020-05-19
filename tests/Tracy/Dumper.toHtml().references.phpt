<?php

/**
 * Test: Tracy\Dumper::toHtml() references
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


$a = 1;
$b = 2;
$obj = (object) [&$a, $a, &$b, $b, (object) [&$a, &$b], (object) [$a, $b], [&$b, &$a]];

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">0</span> => <span class="tracy-dump-hash">&1</span> <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">1</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">2</span> => <span class="tracy-dump-hash">&2</span> <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">3</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">4</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">0</span> => <span class="tracy-dump-hash">&1</span> <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">1</span> => <span class="tracy-dump-hash">&2</span> <span class="tracy-dump-number">2</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">5</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">1</span> => <span class="tracy-dump-number">2</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">6</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-hash">&2</span> <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-hash">&1</span> <span class="tracy-dump-number">1</span>
</div></div></pre>', Dumper::toHtml($obj));
