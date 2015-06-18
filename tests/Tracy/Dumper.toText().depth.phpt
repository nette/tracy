<?php

/**
 * Test: Tracy\Dumper::toText() depth & truncate
 */

use Tracy\Dumper;
use Tester\Assert;


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
$arr[] = &$arr;


Assert::match('array (5)
   long => "Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ... " (15000)
   0 => array (1)
   |  0 => array (1)
   |  |  0 => array (1)
   |  |  |  hello => "world" (5)
   long2 => "Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ... " (15000)
   1 => stdClass #%a%
   |  0 => stdClass #%a%
   |  |  0 => stdClass #%a%
   |  |  |  hello => "world" (5)
   2 => array (5)
   |  long => "Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ... " (15000)
   |  0 => array (1)
   |  |  0 => array (1)
   |  |  |  0 => array (1) [ ... ]
   |  long2 => "Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ... " (15000)
   |  1 => stdClass #%a%
   |  |  0 => stdClass #%a%
   |  |  |  0 => stdClass #%a% { ... }
   |  2 => array (5) [ RECURSION ]
', Dumper::toText($arr));


Assert::match('array (5)
   long => "Nette FrameworkNette FrameworkNette FrameworkNette ... " (15000)
   0 => array (1)
   |  0 => array (1) [ ... ]
   long2 => "Nette FrameworkNette FrameworkNette FrameworkNette ... " (15000)
   1 => stdClass #%a%
   |  0 => stdClass #%a% { ... }
   2 => array (5)
   |  long => "Nette FrameworkNette FrameworkNette FrameworkNette ... " (15000)
   |  0 => array (1) [ ... ]
   |  long2 => "Nette FrameworkNette FrameworkNette FrameworkNette ... " (15000)
   |  1 => stdClass #%a% { ... }
   |  2 => array (5) [ RECURSION ]
', Dumper::toText($arr, [Dumper::DEPTH => 2, Dumper::TRUNCATE => 50]));
