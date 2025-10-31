<?php

/**
 * Test: Tracy\Dumper custom object exporters
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;
use Tracy\Dumper\Node;
use Tracy\Dumper\Nodes\ObjectNode;
use Tracy\Dumper\Nodes\TextNode;

require __DIR__ . '/../bootstrap.php';


// default exposer
$obj = new stdClass;
Assert::match('stdClass #%d%', Dumper::toText($obj));


$obj->a = 1;
Assert::match('stdClass #%d%
   a: 1
', Dumper::toText($obj));


// custom exposer
$exporters = [
	'stdClass' => fn($var) => ['x' => $var->a + 1],
];
Assert::match(
	'stdClass #%d%
   x: 2
',
	Dumper::toText($obj, [Dumper::OBJECT_EXPORTERS => $exporters]),
);


// custom exposer & new way
$exporters = [
	'stdClass' => function ($var, Node $value, Dumper\Describer $describer) {
		$describer->addPropertyTo($value, 'x', $var->a + 2, ObjectNode::PropertyPublic);
		$value->items[] = [$describer->describeKey('key'), new TextNode('hello')];
		$value->items[] = [new TextNode('$x'), new TextNode('hello')];
		$inner = new ObjectNode('hello');
		$describer->addPropertyTo($inner, 'a', 'b', ObjectNode::PropertyPublic);
		$value->items[] = ['object', $inner];
	},
];
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">x</span>: <span class="tracy-dump-number">3</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">key</span>: <span class="tracy-dump-virtual">hello</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">$x</span>: <span class="tracy-dump-virtual">hello</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">object</span>: <span class="tracy-toggle"><span class="tracy-dump-object">hello</span></span>
		<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-public">a</span>: <span class="tracy-dump-string"><span>'</span>b<span>'</span></span>
		</div></div></pre>
		XX,
	Dumper::toHtml($obj, [Dumper::OBJECT_EXPORTERS => $exporters]),
);


// custom exposer & collapsed
$exporters = [
	'stdClass' => function ($var, Node $value, Dumper\Describer $describer) {
		$describer->addPropertyTo($value, 'x', 'y', ObjectNode::PropertyPublic);
		$value->collapsed = true;
	},
];
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light" data-tracy-snapshot='{"%d%":{"object":"stdClass","items":[["x","y",0]],"collapsed":true}}'
		><span class="tracy-toggle tracy-collapsed" data-tracy-dump='{"ref":%d%}'><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span></pre>
		XX,
	Dumper::toHtml($obj, [Dumper::OBJECT_EXPORTERS => $exporters]),
);


// PHP incomplete class
$obj = unserialize('O:1:"Y":7:{s:1:"1";N;s:1:"b";i:2;s:4:"' . "\0" . '*' . "\0" . 'c";N;s:4:"' . "\0" . '*' . "\0" . 'd";s:1:"d";s:4:"' . "\0" . 'Y' . "\0" . 'e";N;s:4:"' . "\0" . 'Y' . "\0" . 'i";s:3:"bar";s:4:"' . "\0" . 'X' . "\0" . 'i";s:3:"foo";}');

Assert::match(
	<<<'XX'
		Y (Incomplete Class) #%d%
		   1: null
		   b: 2
		   c: null
		   d: 'd'
		   e: null
		   i: 'bar'
		   i: 'foo'
		XX,
	Dumper::toText($obj),
);



// inheritance
Dumper::$objectExporters = [
	'' => fn($var) => ['type' => 'NULL'],
	'Iterator' => fn($var) => ['type' => 'Default Iterator'],
];

$exporters = [
	'Iterator' => fn($var) => ['type' => 'Iterator'],
	'SplFileInfo' => fn($var) => ['type' => 'SplFileInfo'],
	'SplFileObject' => fn($var) => ['type' => 'SplFileObject'],
];

Assert::match(
	<<<'XX'
		SplFileInfo #%d%
		   type: 'SplFileInfo'
		XX,
	Dumper::toText(new SplFileInfo(__FILE__), [Dumper::OBJECT_EXPORTERS => $exporters]),
);

Assert::match(
	<<<'XX'
		SplFileObject #%d%
		   type: 'SplFileObject'
		XX,
	Dumper::toText(new SplFileObject(__FILE__), [Dumper::OBJECT_EXPORTERS => $exporters]),
);

Assert::match(
	<<<'XX'
		ArrayIterator #%d%
		   type: 'Iterator'
		XX,
	Dumper::toText(new ArrayIterator([]), [Dumper::OBJECT_EXPORTERS => $exporters]),
);

Assert::match(
	<<<'XX'
		stdClass #%d%
		   type: 'NULL'
		XX,
	Dumper::toText(new stdClass, [Dumper::OBJECT_EXPORTERS => $exporters]),
);

Assert::match(
	<<<'XX'
		ArrayIterator #%d%
		   type: 'Default Iterator'
		XX,
	Dumper::toText(new ArrayIterator([])),
);

Assert::match(
	<<<'XX'
		stdClass #%d%
		   type: 'NULL'
		XX,
	Dumper::toText(new stdClass),
);
