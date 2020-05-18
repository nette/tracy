<?php

/**
 * Test: Tracy\Dumper::toHtml() recursion
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


$arr = [1, 2, 3];
$arr[] = &$arr;
Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">2</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">3</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">2</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">3</span> => <span class="tracy-dump-array">array</span> (4) [ <i>RECURSION</i> ]
</div></div></pre>', Dumper::toHtml($arr));


$arr = (object) ['x' => 1, 'y' => 2];
$arr->z = &$arr;
Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> => <span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span> { <i>RECURSION</i> }
</div></pre>', Dumper::toHtml($arr));
