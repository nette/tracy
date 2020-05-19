<?php

/**
 * Test: Tracy\Dumper::toHtml() specials
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


// resource
$f = fopen(__FILE__, 'r');
Assert::match('<pre class="tracy-dump"><span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">@%d%</span></span>
<div class="tracy-collapsed"><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">%a%</span>: <span class="tracy-dump-bool">%a%</span>%A%', Dumper::toHtml($f));

fclose($f);
Assert::match('<pre class="tracy-dump"><span class="tracy-dump-resource">closed resource</span> <span class="tracy-dump-hash">@%d%</span>
</pre>', Dumper::toHtml($f));


// closure
Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Closure</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">file</span>: <span class="tracy-dump-string">"%a%"</span> (%i%)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">line</span>: <span class="tracy-dump-number">%i%</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">variables</span>: <span class="tracy-dump-array">array</span> ()
<span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">parameters</span>: <span class="tracy-dump-string">""</span>
</div></pre>', Dumper::toHtml(function () {}));


// new class
Assert::match('<pre class="tracy-dump"><span class="tracy-dump-object">class@anonymous</span> <span class="tracy-dump-hash">#%d%</span>
</pre>', Dumper::toHtml(new class {
}));


// SplFileInfo
Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">SplFileInfo</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">path</span>: <span class="tracy-dump-string">"%a%"</span> (%d%)
</div></pre>', Dumper::toHtml(new SplFileInfo(__FILE__)));


// SplObjectStorage
$objStorage = new SplObjectStorage;
$objStorage->attach($o1 = new stdClass);
$objStorage[$o1] = 'o1';
$objStorage->attach($o2 = (object) ['foo' => 'bar']);
$objStorage[$o2] = 'o2';

$objStorage->next();
$key = $objStorage->key();

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">SplObjectStorage</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">0</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">object</span> => <span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">data</span> => <span class="tracy-dump-string">"o1"</span> (2)
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">1</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">object</span> => <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-dynamic">foo</span>: <span class="tracy-dump-string">"bar"</span> (3)
</div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">data</span> => <span class="tracy-dump-string">"o2"</span> (2)
</div></div></pre>', Dumper::toHtml($objStorage));

Assert::same($key, $objStorage->key());
