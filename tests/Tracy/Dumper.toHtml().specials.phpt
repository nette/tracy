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
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">@%d%</span></span>
		<div class="tracy-collapsed"><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">%a%</span>: <span class="tracy-dump-bool">%a%</span>%A%
		XX,
	Dumper::toHtml($f),
);

fclose($f);
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"><span class="tracy-dump-resource">closed resource</span> <span class="tracy-dump-hash">@%d%</span></pre>
		XX,
	Dumper::toHtml($f),
);


// closure
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"><span class="tracy-dump-object">Closure()</span> <span class="tracy-dump-hash">#%d%</span></pre>
		XX,
	Dumper::toHtml(function () {}),
);


Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle"><span class="tracy-dump-object" title="Declared in file %a% on line %d%&#10;Ctrl-Click to open in editor&#10;Alt-Click to expand/collapse all child nodes" data-tracy-href="editor://open/?file=%a%&amp;line=%d%&amp;search=&amp;replace=">Closure()</span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">file</span>: <span class="tracy-dump-string" title="%d% characters"><span>'</span>%a%:%d%<span>'</span></span>
		</div></pre>
		XX,
	Dumper::toHtml(function () {}, [Dumper::LOCATION => Dumper::LOCATION_CLASS]),
);


Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light" data-tracy-snapshot='[]'
		><span class="tracy-toggle"><span class="tracy-dump-object">Closure($x, $y)</span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">use</span>: <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"object":"$use","items":[["$use",null,4]],"collapsed":true}'><span class="tracy-dump-object">$use</span></span>
		</div></pre>
		XX,
	Dumper::toHtml(function ($x, int $y = 1) use (&$use) {}),
);


// new class
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"><span class="tracy-dump-object">class@anonymous</span> <span class="tracy-dump-hash">#%d%</span></pre>
		XX,
	Dumper::toHtml(new class {
	}),
);


// SplFileInfo
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle"><span class="tracy-dump-object">SplFileInfo</span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">path</span>: <span class="tracy-dump-string" title="%d% characters"><span>'</span>%a%<span>'</span></span>
		</div></pre>
		XX,
	Dumper::toHtml(new SplFileInfo(__FILE__)),
);


// SplObjectStorage
$objStorage = new SplObjectStorage;
$objStorage->attach($o1 = new stdClass);
$objStorage[$o1] = 'o1';
$objStorage->attach($o2 = (object) ['foo' => 'bar']);
$objStorage[$o2] = 'o2';

$objStorage->next();
$key = $objStorage->key();

Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light" data-tracy-snapshot='{"%d%":{"object":"stdClass","items":[["foo","bar",3]]}}'
		><span class="tracy-toggle"><span class="tracy-dump-object">SplObjectStorage (2)</span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual"></span>: <span class="tracy-toggle"><span class="tracy-dump-object"></span></span>
		<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-virtual">key</span>: <span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span>
		<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-virtual">value</span>: <span class="tracy-dump-string" title="2 characters"><span>'</span>o1<span>'</span></span>
		</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual"></span>: <span class="tracy-toggle"><span class="tracy-dump-object"></span></span>
		<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-virtual">key</span>: <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"ref":%d%}'><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
		<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-virtual">value</span>: <span class="tracy-dump-string" title="2 characters"><span>'</span>o2<span>'</span></span>
		</div></div></pre>
		XX,
	Dumper::toHtml($objStorage),
);

Assert::same($key, $objStorage->key());


// WeakMap
$weakmap = new WeakMap;
$weakmap[$o1] = 'o1';
$weakmap[$o2] = 'o2';

Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light" data-tracy-snapshot='{"%d%":{"object":"stdClass","items":[["foo","bar",3]]}}'
		><span class="tracy-toggle"><span class="tracy-dump-object">WeakMap (2)</span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual"></span>: <span class="tracy-toggle"><span class="tracy-dump-object"></span></span>
		<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-virtual">key</span>: <span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span>
		<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-virtual">value</span>: <span class="tracy-dump-string" title="2 characters"><span>'</span>o1<span>'</span></span>
		</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual"></span>: <span class="tracy-toggle"><span class="tracy-dump-object"></span></span>
		<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-virtual">key</span>: <span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"ref":%d%}'><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
		<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-virtual">value</span>: <span class="tracy-dump-string" title="2 characters"><span>'</span>o2<span>'</span></span>
		</div></div></pre>
		XX,
	Dumper::toHtml($weakmap),
);


// ArrayObject
$obj = new ArrayObject(['a' => 1, 'b' => 2]);
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle"><span class="tracy-dump-object">ArrayObject (2)</span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-private" title="declared in ArrayObject">storage</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
		<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>a<span>'</span></span> => <span class="tracy-dump-number">1</span>
		<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>b<span>'</span></span> => <span class="tracy-dump-number">2</span>
		</div></div></pre>
		XX,
	Dumper::toHtml($obj),
);

class ArrayObjectChild extends ArrayObject
{
	public $prop = 123;
}

$obj = new ArrayObjectChild(['a' => 1, 'b' => 2]);
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle"><span class="tracy-dump-object">ArrayObjectChild (2)</span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">prop</span>: <span class="tracy-dump-number">123</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-private" title="declared in ArrayObject">storage</span>: <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
		<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>a<span>'</span></span> => <span class="tracy-dump-number">1</span>
		<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>b<span>'</span></span> => <span class="tracy-dump-number">2</span>
		</div></div></pre>
		XX,
	Dumper::toHtml($obj),
);


// ArrayIterator
$obj = new ArrayIterator(['a', 'b']);
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle"><span class="tracy-dump-object">ArrayIterator</span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">0</span>: <span class="tracy-dump-string"><span>'</span>a<span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">1</span>: <span class="tracy-dump-string"><span>'</span>b<span>'</span></span>
		</div></pre>
		XX,
	Dumper::toHtml($obj),
);


// DateTime
$obj = new DateTime('1978-01-23');
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle"><span class="tracy-dump-object">DateTime</span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">date</span>: <span class="tracy-dump-string" title="26 characters"><span>'</span>1978-01-23 00:00:00.000000<span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">timezone_type</span>: <span class="tracy-dump-number">3</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">timezone</span>: <span class="tracy-dump-string" title="%d% characters"><span>'</span>%a%<span>'</span></span>
		</div></pre>
		XX,
	Dumper::toHtml($obj),
);


// Tracy\Dumper\Value
$obj = new Tracy\Dumper\Value(Tracy\Dumper\Value::TypeText, 'ahoj');
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle"><span class="tracy-dump-object">Tracy\Dumper\<b>Value</b></span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">type</span>: <span class="tracy-dump-string" title="4 characters"><span>'</span>text<span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">value</span>: <span class="tracy-dump-string" title="4 characters"><span>'</span>ahoj<span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">length</span>: <span class="tracy-dump-null">null</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">depth</span>: <span class="tracy-dump-null">null</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">id</span>: <span class="tracy-dump-null">null</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">holder</span>: <span class="tracy-dump-virtual">unset</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">items</span>: <span class="tracy-dump-null">null</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">editor</span>: <span class="tracy-dump-null">null</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-public">collapsed</span>: <span class="tracy-dump-null">null</span>
		</div></pre>
		XX,
	Dumper::toHtml($obj),
);
