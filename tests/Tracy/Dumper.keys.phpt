<?php

declare(strict_types=1);

use Tester\Assert;
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

Assert::match('stdClass #%d%
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
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":%d%}\'></pre>',
	Dumper::toHtml((object) $keys, [Dumper::SNAPSHOT => &$snapshot])
);

Assert::equal([
	[
		'name' => 'stdClass',
		'items' => [
			['""', 0, 3],
			['"""', 0, 3],
			['"\'"', 0, 3],
			['key', 0, 3],
			['" key"', 0, 3],
			['"key "', 0, 3],
			[0, 0, 3],
			['01', 0, 3],
			['"true"', 0, 3],
			['"false"', 0, 3],
			['"null"', 0, 3],
			['"NULL"', 0, 3],
		],
	],
], array_values(json_decode(explode("'", Dumper::formatSnapshotAttribute($snapshot))[1], true)));
