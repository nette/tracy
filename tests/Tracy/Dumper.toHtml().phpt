<?php

/**
 * Test: Tracy\Dumper::toHtml()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


// scalars & empty array
Assert::same('<pre class="tracy-dump"><span class="tracy-dump-null">null</span></pre>' . "\n", Dumper::toHtml(null));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-bool">true</span></pre>' . "\n", Dumper::toHtml(true));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-bool">false</span></pre>' . "\n", Dumper::toHtml(false));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-number">0</span></pre>' . "\n", Dumper::toHtml(0));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-number">1</span></pre>' . "\n", Dumper::toHtml(1));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-number">0.0</span></pre>' . "\n", Dumper::toHtml(0.0));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-number">0.1</span></pre>' . "\n", Dumper::toHtml(0.1));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-number">INF</span></pre>' . "\n", Dumper::toHtml(INF));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-number">-INF</span></pre>' . "\n", Dumper::toHtml(-INF));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-number">NAN</span></pre>' . "\n", Dumper::toHtml(NAN));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-string">\'\'</span></pre>' . "\n", Dumper::toHtml(''));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-string">\'0\'</span></pre>' . "\n", Dumper::toHtml('0'));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-string">\'<span>\\x00</span>\'</span></pre>' . "\n", Dumper::toHtml("\x00"));

Assert::same('<pre class="tracy-dump"><span class="tracy-dump-array">array</span> (0)</pre>' . "\n", Dumper::toHtml([]));


// array
Assert::same(str_replace("\r", '', <<<'XX'
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">1</span>
</div></pre>

XX
), Dumper::toHtml([1]));


// array (with snapshot)
Assert::match(<<<'XX'
<pre class="tracy-dump" data-tracy-snapshot='[]'><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (5)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-string" title="5 characters">'hello'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-array">array</span> (0)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">3</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-number">2</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">4</span> => <span class="tracy-toggle tracy-collapsed" data-tracy-dump='[[1,1],[2,2],[3,3],[4,4],[5,5],[6,6],[7,7]]'><span class="tracy-dump-array">array</span> (7)</span>
</div></pre>
XX
, Dumper::toHtml([1, 'hello', [], [1, 2], [1 => 1, 2, 3, 4, 5, 6, 7]]));


// object
Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></pre>
XX
, Dumper::toHtml(new stdClass));

Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">x</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">10</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-null">null</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-private" title="declared in Test">y</span>: <span class="tracy-dump-string" title="5 characters">'hello'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-protected">z</span>: <span class="tracy-dump-number">30.0</span>
</div></pre>
XX
, Dumper::toHtml(new Test));

$obj = new Child;
$obj->new = 7;
$obj->{0} = 8;
$obj->{1} = 9;
$obj->{''} = 10;
$obj->{"a\x00\n"} = 11;
$obj->{"a\xA0"} = 12;

Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Child</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">x</span>: <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-private" title="declared in Child">y</span>: <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-protected">z</span>: <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">x2</span>: <span class="tracy-dump-number">4</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-protected">y2</span>: <span class="tracy-dump-number">5</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-private" title="declared in Child">z2</span>: <span class="tracy-dump-number">6</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-private" title="declared in Test">y</span>: <span class="tracy-dump-string" title="5 characters">'hello'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">new</span>: <span class="tracy-dump-number">7</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">0</span>: <span class="tracy-dump-number">8</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">1</span>: <span class="tracy-dump-number">9</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">&apos;&apos;</span>: <span class="tracy-dump-number">10</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">'a<span>\x00\n</span>
 '</span>: <span class="tracy-dump-number">11</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">'a<span>\xA0</span>'</span>: <span class="tracy-dump-number">12</span>
</div></pre>
XX
, Dumper::toHtml($obj));
