<?php

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


$obj = (object) [
	'a' => 456,
	'password' => 'secret1',
	'PASSWORD' => 'secret2',
	'Pin' => 'secret3',
	'inner' => [
		'a' => 123,
		'password' => 'secret4',
		'PASSWORD' => 'secret5',
		'Pin' => 'secret6',
	],
];


Assert::match(<<<'XX'
stdClass #%d%
   a: ***** (integer)
   password: ***** (string)
   PASSWORD: ***** (string)
   Pin: ***** (string)
   inner: array (4)
   |  'a' => 123
   |  'password' => ***** (string)
   |  'PASSWORD' => ***** (string)
   |  'Pin' => ***** (string)
XX
	, Dumper::toText($obj, [Dumper::KEYS_TO_HIDE => ['password', 'PIN', 'stdClass::$a']]));


$snapshot = [];
Assert::match(
	'<pre class="tracy-dump tracy-light" data-tracy-dump=\'{"ref":%d%}\'></pre>',
	Dumper::toHtml($obj, [Dumper::KEYS_TO_HIDE => ['password', 'pin'], Dumper::SNAPSHOT => &$snapshot])
);

Assert::equal([
	[
		'object' => 'stdClass',
		'items' => [
			['a', 456, 3],
			['password', ['text' => '***** (string)'], 3],
			['PASSWORD', ['text' => '***** (string)'], 3],
			['Pin', ['text' => '***** (string)'], 3],
			[
				'inner',
				[
					['a', 123],
					['password', ['text' => '***** (string)']],
					['PASSWORD', ['text' => '***** (string)']],
					['Pin', ['text' => '***** (string)']],
				],
				3,
			],
		],
	],
], array_values(json_decode(explode("'", Dumper::formatSnapshotAttribute($snapshot))[1], true)));
