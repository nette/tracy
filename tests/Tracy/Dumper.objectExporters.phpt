<?php

/**
 * Test: Tracy\Dumper custom object exporters
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;
use Tracy\Dumper\Value;

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
	'stdClass' => function ($var) {
		return ['x' => $var->a + 1];
	},
];
Assert::match('stdClass #%d%
   x: 2
', Dumper::toText($obj, [Dumper::OBJECT_EXPORTERS => $exporters])
);


// custom exposer & new way
$exporters = [
	'stdClass' => function ($var, Value $value, Dumper\Describer $describer) {
		$describer->addPropertyTo($value, 'x', $var->a + 2, Value::PROP_PUBLIC);
		$value->items[] = [$describer->describeKey('key'), new Value('text', 'hello')];
	},
];
Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%d%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">x</span>: <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-virtual">key</span>: <span>hello</span>
</div></pre>
XX
, Dumper::toHtml($obj, [Dumper::OBJECT_EXPORTERS => $exporters]));


// PHP incomplete class
$obj = unserialize('O:1:"Y":7:{s:1:"1";N;s:1:"b";i:2;s:4:"' . "\0" . '*' . "\0" . 'c";N;s:4:"' . "\0" . '*' . "\0" . 'd";s:1:"d";s:4:"' . "\0" . 'Y' . "\0" . 'e";N;s:4:"' . "\0" . 'Y' . "\0" . 'i";s:3:"bar";s:4:"' . "\0" . 'X' . "\0" . 'i";s:3:"foo";}');

Assert::match(<<<'XX'
__PHP_Incomplete_Class #%d%
   className: 'Y'
   private: array (3)
   |  'Y::$e' => null
   |  'Y::$i' => 'bar' (3)
   |  'X::$i' => 'foo' (3)
   protected: array (2)
   |  c => null
   |  d => 'd'
   public: array (2)
   |  1 => null
   |  b => 2
XX
, Dumper::toText($obj));



// inheritance
Dumper::$objectExporters = [
	null => function ($var) { return ['type' => 'NULL']; },
	'Iterator' => function ($var) { return ['type' => 'Default Iterator']; },
];

$exporters = [
	'Iterator' => function ($var) { return ['type' => 'Iterator']; },
	'SplFileInfo' => function ($var) { return ['type' => 'SplFileInfo']; },
	'SplFileObject' => function ($var) { return ['type' => 'SplFileObject']; },
];

Assert::match(<<<'XX'
SplFileInfo #%d%
   type: 'SplFileInfo' (11)
XX
, Dumper::toText(new SplFileInfo(__FILE__), [Dumper::OBJECT_EXPORTERS => $exporters]));

Assert::match(<<<'XX'
SplFileObject #%d%
   type: 'SplFileObject' (13)
XX
, Dumper::toText(new SplFileObject(__FILE__), [Dumper::OBJECT_EXPORTERS => $exporters]));

Assert::match(<<<'XX'
ArrayIterator #%d%
   type: 'Iterator' (8)
XX
, Dumper::toText(new ArrayIterator([]), [Dumper::OBJECT_EXPORTERS => $exporters]));

Assert::match(<<<'XX'
stdClass #%d%
   type: 'NULL' (4)
XX
, Dumper::toText(new stdClass, [Dumper::OBJECT_EXPORTERS => $exporters]));

Assert::match(<<<'XX'
ArrayIterator #%d%
   type: 'Default Iterator' (16)
XX
, Dumper::toText(new ArrayIterator([])));

Assert::match(<<<'XX'
stdClass #%d%
   type: 'NULL' (4)
XX
, Dumper::toText(new stdClass));
