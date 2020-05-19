<?php

/**
 * Test: Tracy\Dumper::toText() special chars
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


Assert::match(<<<XX
array (10)
   0 => ''
   1 => ' '
   2 => '\\x00'
   3 => '\\xFF'
   4 => 'Iñtërnâtiônàlizætiøn'
   5 =>\n   'utf \\n\n    \\r\\t\t\\e\\x00 Iñtër'
   6 => 'utf \\n\\r\\t\\xab Iñtër'
   7 =>
   'binary \\n\n    \\r\\t\t\\e\\x00 I\\xC3\\xB1t\\xC3\\xABr \\xA0'
   8 => 'binary \\n\\r\\t\\xab I\\xC3\\xB1t\\xC3\\xABr \\xA0'
   'utf \\n\n \\r\\t\t\\e\\x00 Iñtër' =>
   'utf \\n\n    \\r\\t\t\\e\\x00 Iñtër'
XX
, Dumper::toText([
	'',
	' ',
	"\x00",
	"\xFF",
	"I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n", // Iñtërnâtiônàlizætiøn,
	"utf \n\r\t\e\x00 Iñtër", // utf + control chars 
	'utf \n\r\t\xab Iñtër', // slashes
	"binary \n\r\t\e\x00 Iñtër \xA0", // binary + control chars
	'binary \n\r\t\xab Iñtër ' . "\xA0", // binary + slashes
	"utf \n\r\t\e\x00 Iñtër" => "utf \n\r\t\e\x00 Iñtër", // utf + control chars in key
]));
