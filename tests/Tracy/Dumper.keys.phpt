<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Expect;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


$keys = [
	'' => 0,
	'"' => 0,
	"'" => 0,
	'key' => 0,
	' key' => 0,
	'key ' => 0,
	0 => 0,
	'01' => 0,
	'true' => 0,
	'false' => 0,
	'null' => 0,
	'NULL' => 0,
];

Assert::match('array (%i%)
   "" => 0
   """ => 0
   "\'" => 0
   key => 0
   " key" => 0
   "key " => 0
   0 => 0
   01 => 0
   "true" => 0
   "false" => 0
   "null" => 0
   "NULL" => 0
', Dumper::toText($keys));

Assert::match('stdClass #%a%
   "" => 0
   """ => 0
   "\'" => 0
   key => 0
   " key" => 0
   "key " => 0
   0 => 0
   01 => 0
   "true" => 0
   "false" => 0
   "null" => 0
   "NULL" => 0
', Dumper::toText((object) $keys));


$snapshot = [];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":1}\'></pre>',
	Dumper::toHtml((object) $keys, [Dumper::SNAPSHOT => &$snapshot])
);

Assert::equal([
	1 => [
		'name' => 'stdClass',
		'hash' => Expect::match('%h%'),
		'items' => [
			['""', 0, 0],
			['"""', 0, 0],
			['"\'"', 0, 0],
			['key', 0, 0],
			['" key"', 0, 0],
			['"key "', 0, 0],
			[0, 0, 0],
			['01', 0, 0],
			['"true"', 0, 0],
			['"false"', 0, 0],
			['"null"', 0, 0],
			['"NULL"', 0, 0],
		],
	],
], json_decode(explode("'", Dumper::formatSnapshotAttribute($snapshot))[1], true));
