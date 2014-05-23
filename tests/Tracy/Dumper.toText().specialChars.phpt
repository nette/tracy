<?php

/**
 * Test: Tracy\Dumper::toText() special chars
 */

use Tracy\Dumper,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::match( "array (9)
   0 => \"\"
   1 => \" \"
   2 => \"utf \n\r\t string\" (14)
   3 => \"binary \\n\\r\\t string\\x00\" (18)
   4 => \"utf \\n\\r\\t\\xab string\" (21)
   5 => \"binary \\\\n\\\\r\\\\t\\\\xab string\\x00\" (25)
   6 => \"Iñtërnâtiônàlizætiøn\" (27)
   7 => \"\\x00\"
   8 => \"\\xff\"
", Dumper::toText(array(
	'',
	' ',
	"utf \n\r\t string",
	"binary \n\r\t string\x00",
	'utf \n\r\t\xab string',
	'binary \n\r\t\xab string' . "\x00",
	"I\xc3\xb1t\xc3\xabrn\xc3\xa2ti\xc3\xb4n\xc3\xa0liz\xc3\xa6ti\xc3\xb8n", // Iñtërnâtiônàlizætiøn,
	"\x00",
	"\xFF",
)));
