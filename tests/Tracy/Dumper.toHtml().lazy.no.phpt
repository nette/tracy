<?php

/**
 * Test: Tracy\Dumper::toHtml() lazy => false
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


// no lazy dump of scalars & empty array
$options = [Dumper::LAZY => false, Dumper::THEME => false];

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span></pre>', Dumper::toHtml(null, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-bool">true</span></pre>', Dumper::toHtml(true, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0</span></pre>', Dumper::toHtml(0, $options));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-array">array</span> (0)</pre>', Dumper::toHtml([], $options));


// no lazy dump of array
Assert::match(<<<'XX'
<pre class="tracy-dump"
><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (11)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-null">null</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-bool">true</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-bool">false</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-number">0</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">4</span> => <span class="tracy-dump-number">0.0</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">5</span> => <span class="tracy-dump-string" title="6 characters"><span>'</span>string<span>'</span></span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">6</span> => <span class="tracy-dump-string" title="3 characters"><span>'</span>'&amp;"<span>'</span></span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">7</span> => <span class="tracy-dump-string"><span>'</span><i>\x00</i><span>'</span></span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">8</span> => <span class="tracy-dump-number">INF</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">9</span> => <span class="tracy-dump-number">-INF</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">10</span> => <span class="tracy-dump-number">NAN</span>
</div></pre>
XX
	, Dumper::toHtml([null, true, false, 0, 0.0, 'string', "'&\"", "\x00", INF, -INF, NAN], $options));


// no lazy dump and resource
Assert::match(<<<'XX'
<pre class="tracy-dump"
><span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">@%d%</span></span>
<div class="tracy-collapsed">%A%
XX
	, Dumper::toHtml(fopen(__FILE__, 'r'), $options));


// no lazy dump and collapse
Assert::match(<<<'XX'
<pre class="tracy-dump"
><span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div class="tracy-collapsed"><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">x</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">10</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-null">null</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-private" title="declared in Test">y</span>: <span class="tracy-dump-string" title="5 characters"><span>'</span>hello<span>'</span></span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-protected">z</span>: <span class="tracy-dump-number">30.0</span>
</div></pre>
XX
	, Dumper::toHtml(new Test, $options + [Dumper::COLLAPSE => true]));


// no lazy dump & location
Assert::match(<<<'XX'
<pre class="tracy-dump"
><a href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=" class="tracy-dump-location" title="in file %a% on line %d%&#10;Click to open in editor">Dumper::toHtml(new Test, $options + ['location' => <span>…</span> N_CLASS])) 📍</a
><span class="tracy-toggle"><span class="tracy-dump-object" title="Declared in file %a% on line %d%&#10;Ctrl-Click to open in editor&#10;Alt-Click to expand/collapse all child nodes" data-tracy-href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=">Test</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">x</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">10</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-null">null</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-private" title="declared in Test">y</span>: <span class="tracy-dump-string" title="5 characters"><span>'</span>hello<span>'</span></span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-protected">z</span>: <span class="tracy-dump-number">30.0</span>
</div></pre>
XX
	, Dumper::toHtml(new Test, $options + ['location' => Dumper::LOCATION_SOURCE | Dumper::LOCATION_CLASS]));


// recursion
$arr = [1, 2, 3];
$arr[] = &$arr;
Assert::match(<<<'XX'
<pre class="tracy-dump"
><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-hash">&1</span> <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (4)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-hash">&1</span> <span class="tracy-dump-array">array</span> (4) <i>RECURSION</i>
</div></div></pre>
XX
	, Dumper::toHtml($arr, $options));

$obj = new stdClass;
$obj->x = $obj;
Assert::match(<<<'XX'
<pre class="tracy-dump"
><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">x</span>: <span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span> <i>RECURSION</i>
</div></pre>
XX
	, Dumper::toHtml($obj, $options));


// max depth
$arr = [1, [2, [3, [4, [5, [6]]]]], 3];
Assert::match(<<<'XX'
<pre class="tracy-dump"
><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (3)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">4</span>
<span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-array">array</span> (2) …
</div></div></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-number">3</span>
</div></pre>
XX
	, Dumper::toHtml($arr, $options + [Dumper::DEPTH => 4]));

$obj = new stdClass;
$obj->a = new stdClass;
$obj->a->b = new stdClass;
$obj->a->b->c = new stdClass;
$obj->a->b->c->d = new stdClass;
$obj->a->b->c->d->e = new stdClass;
Assert::match(<<<'XX'
<pre class="tracy-dump"
><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-dynamic">a</span>: <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-dynamic">b</span>: <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  |  </span><span class="tracy-dump-dynamic">c</span>: <span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   |  |  |  </span><span class="tracy-dump-dynamic">d</span>: <span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span> …
</div></div></div></div></pre>
XX
	, Dumper::toHtml($obj, $options + [Dumper::DEPTH => 4]));
