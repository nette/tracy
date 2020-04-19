<?php

/**
 * Test: Tracy\Dumper custom object exporters
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


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
	'stdClass' => function ($var, Dumper\Structure $struct, Dumper\Describer $describer) {
		$describer->addProperty($struct, 'x', $var->a + 2, Dumper\Exposer::PROP_PUBLIC);
	},
];
Assert::match('stdClass #%d%
   x: 3
', Dumper::toText($obj, [Dumper::OBJECT_EXPORTERS => $exporters])
);


// PHP incomplete class
$obj = unserialize('O:1:"Y":7:{s:1:"a";N;s:1:"b";i:2;s:4:"' . "\0" . '*' . "\0" . 'c";N;s:4:"' . "\0" . '*' . "\0" . 'd";s:1:"d";s:4:"' . "\0" . 'Y' . "\0" . 'e";N;s:4:"' . "\0" . 'Y' . "\0" . 'i";s:3:"bar";s:4:"' . "\0" . 'X' . "\0" . 'i";s:3:"foo";}');

Assert::match(<<<'XX'
__PHP_Incomplete_Class #%d%
   className: 'Y'
   private: array (3)
   |  'Y::$e' => null
   |  'Y::$i' => 'bar'
   |  'X::$i' => 'foo'
   protected: array (2)
   |  c => null
   |  d => 'd'
   public: array (2)
   |  a => null
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
   type: 'SplFileInfo'
XX
, Dumper::toText(new SplFileInfo(__FILE__), [Dumper::OBJECT_EXPORTERS => $exporters]));

Assert::match(<<<'XX'
SplFileObject #%d%
   type: 'SplFileObject'
XX
, Dumper::toText(new SplFileObject(__FILE__), [Dumper::OBJECT_EXPORTERS => $exporters]));

Assert::match(<<<'XX'
ArrayIterator #%d%
   type: 'Iterator'
XX
, Dumper::toText(new ArrayIterator([]), [Dumper::OBJECT_EXPORTERS => $exporters]));

Assert::match(<<<'XX'
stdClass #%d%
   type: 'NULL'
XX
, Dumper::toText(new stdClass, [Dumper::OBJECT_EXPORTERS => $exporters]));

Assert::match(<<<'XX'
ArrayIterator #%d%
   type: 'Default Iterator'
XX
, Dumper::toText(new ArrayIterator([])));

Assert::match(<<<'XX'
stdClass #%d%
   type: 'NULL'
XX
, Dumper::toText(new stdClass));
