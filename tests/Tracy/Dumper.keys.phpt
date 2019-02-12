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
', Dumper::toText((object) $keys));


$snapshot = [];
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":"01"}\'></pre>',
	Dumper::toHtml((object) $keys, [Dumper::SNAPSHOT => &$snapshot])
);

Assert::same([
	'01' => [
		'name' => 'stdClass',
		'editor' => null,
		'items' => [
			['""', 0, 0],
			['"""', 0, 0],
			['"\'"', 0, 0],
			['key', 0, 0],
			['" key"', 0, 0],
			['"key "', 0, 0],
			[0, 0, 0],
			['01', 0, 0],
		],
	],
], json_decode(explode("'", Dumper::formatSnapshotAttribute($snapshot))[1], true));
