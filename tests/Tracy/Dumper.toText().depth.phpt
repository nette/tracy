<?php

/**
 * Test: Tracy\Dumper::toText() depth & truncate & items
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


$arr = [
	'long' => str_repeat('Nette Framework', 1000),

	[
		[
			['hello' => 'world'],
		],
	],

	'long2' => str_repeat('Nette Framework', 1000),

	(object) [
		(object) [
			(object) ['hello' => 'world'],
		],
	],
];


Assert::match(<<<'XX'
array (4)
   'long' => 'Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ...  Framework'
   0 => array (1)
   |  0 => array (1)
   |  |  0 => array (1)
   |  |  |  'hello' => 'world'
   'long2' => 'Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ...  Framework'
   1 => stdClass #%a%
   |  0: stdClass #%a%
   |  |  0: stdClass #%a%
   |  |  |  hello: 'world'
XX
, Dumper::toText($arr));


Assert::match(<<<'XX'
array (4)
   'long' => 'Nette FrameworkNette FrameworkNette FrameworkNette ...  Framework'
   0 => array (1)
   |  0 => array (1) ...
   'long2' => 'Nette FrameworkNette FrameworkNette FrameworkNette ...  Framework'
   1 => stdClass #%a%
   |  0: stdClass #%a% ...
XX
, Dumper::toText($arr, [Dumper::DEPTH => 2, Dumper::TRUNCATE => 50]));


$arr = [1, 2, 3, 4, 5, 6];

Assert::match(<<<'XX'
array (2)
   0 => array (6)
   |  0 => 1
   |  1 => 2
   |  2 => 3
   |  3 => 4
   |  4 => 5
   |  ...
   1 => stdClass #%d%
   |  0: 1
   |  1: 2
   |  2: 3
   |  3: 4
   |  4: 5
   |  ...
XX
, Dumper::toText([$arr, (object) $arr], [Dumper::ITEMS => 5]));
