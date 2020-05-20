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
	'<a> &amp;' => 0,
];

Assert::match(<<<'XX'
array (%i%)
   '' => 0
   '"' => 0
   ''' => 0
   'key' => 0
   ' key' => 0
   'key ' => 0
   0 => 0
   '01' => 0
   'true' => 0
   'false' => 0
   'null' => 0
   'NULL' => 0
   '<a> &amp;' => 0
XX
, Dumper::toText($keys));

Assert::match(<<<'XX'
stdClass #%d%
   '': 0
   '"': 0
   ''': 0
   key: 0
   ' key': 0
   'key ': 0
   0: 0
   01: 0
   'true': 0
   'false': 0
   'null': 0
   'NULL': 0
   '<a> &amp;': 0
XX
, Dumper::toText((object) $keys));


$snapshot = [];
Assert::match(
	'<pre class="tracy-dump tracy-light" data-tracy-dump=\'{"ref":%d%}\'></pre>',
	Dumper::toHtml((object) $keys, [Dumper::SNAPSHOT => &$snapshot])
);

Assert::equal([
	[
		'object' => 'stdClass',
		'items' => [
			["''", 0, 3],
			["'\"'", 0, 3],
			["'''", 0, 3],
			['key', 0, 3],
			["' key'", 0, 3],
			["'key '", 0, 3],
			['0', 0, 3],
			['01', 0, 3],
			["'true'", 0, 3],
			["'false'", 0, 3],
			["'null'", 0, 3],
			["'NULL'", 0, 3],
			[['string' => '\'&lt;a> &amp;amp;\'', 'length' => 11], 0, 3],
		],
	],
], array_values(json_decode(explode("'", Dumper::formatSnapshotAttribute($snapshot))[1], true)));
