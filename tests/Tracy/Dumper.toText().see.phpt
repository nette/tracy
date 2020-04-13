<?php

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


class Test
{
	public $a = [1 => [2 => [3 => 'item']]];
}

$obj = new Test;
$arr = [1 => [2 => [3 => 'item']]];
$file = fopen(__FILE__, 'r');

$var = [
	'a' => (object) [
		'b' => [
			'c' => [$obj, new Test, &$arr, $arr, $file],
		],
		$obj,
		new Test,
		&$arr,
		$arr,
		$file,
	],
	$obj,
	new Test,
	&$arr,
	$arr,
	$file,
];


Assert::match(<<<'XX'
array (6)
   'a' => stdClass #%d%
   |  b: array (1)
   |  |  'c' => array (5)
   |  |  |  0 => Test #%d%
   |  |  |  |  a: array (1)
   |  |  |  |  |  1 => array (1)
   |  |  |  |  |  |  2 => array (1) ...
   |  |  |  1 => Test #%d% ...
   |  |  |  2 => &1 array (1)
   |  |  |  |  1 => array (1)
   |  |  |  |  |  2 => array (1)
   |  |  |  |  |  |  3 => 'item'
   |  |  |  3 => array (1) ...
   |  |  |  4 => stream resource @%d%
   |  |  |  |  timed_out: false
   |  |  |  |  blocked: true
   |  |  |  |  eof: false
   |  |  |  |  wrapper_type: 'plainfile'
   |  |  |  |  stream_type: 'STDIO'
   |  |  |  |  mode: 'r'
   |  |  |  |  unread_bytes: 0
   |  |  |  |  seekable: true
   |  |  |  |  uri: '%a%'
   |  0: Test #%d% see above
   |  1: Test #%d%
   |  |  a: array (1)
   |  |  |  1 => array (1) ...
   |  2: &1 array (1) see above
   |  3: array (1)
   |  |  1 => array (1)
   |  |  |  2 => array (1) ...
   |  4: stream resource @%d% see above
   0 => Test #%d% see above
   1 => Test #%d%
   |  a: array (1)
   |  |  1 => array (1)
   |  |  |  2 => array (1) ...
   2 => &1 array (1) see above
   3 => array (1)
   |  1 => array (1)
   |  |  2 => array (1)
   |  |  |  3 => 'item'
   4 => stream resource @%d% see above
XX
, Dumper::toText($var, [Dumper::DEPTH => 4]));
