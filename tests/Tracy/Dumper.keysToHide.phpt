<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Value;
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


Assert::match('stdClass #%a%
   a => 456
   password => "*****" (5)
   PASSWORD => "*****" (5)
   Pin => "*****" (5)
   inner => array (4)
   |  a => 123
   |  password => "*****" (5)
   |  PASSWORD => "*****" (5)
   |  Pin => "*****" (5)
', Dumper::toText($obj, [Dumper::KEYS_TO_HIDE => ['password', 'PIN']]));


$snapshot = [];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":1}\'></pre>',
	Dumper::toHtml($obj, [Dumper::KEYS_TO_HIDE => ['password', 'pin'], Dumper::SNAPSHOT => &$snapshot])
);

Assert::equal([
	1 => [
		'name' => 'stdClass',
		'hash' => Value::match('%h%'),
		'editor' => null,
		'items' => [
			['a', 456, 0],
			['password', '*****', 0],
			['PASSWORD', '*****', 0],
			['Pin', '*****', 0],
			[
				'inner',
				[
					['a', 123],
					['password', '*****'],
					['PASSWORD', '*****'],
					['Pin', '*****'],
				],
				0,
			],
		],
	],
], json_decode(explode("'", Dumper::formatSnapshotAttribute($snapshot))[1], true));
