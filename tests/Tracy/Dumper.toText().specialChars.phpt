<?php

/**
 * Test: Tracy\Dumper::toText() special chars
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


Assert::match(
	<<<'XX'
		array (14)
		   0 => ''
		   1 => ' '
		   2 => '\x00'
		   3 => '\xFF'
		   4 => 'Iñtërnâtiônàlizætiøn'
		   5 => string
		   |  'utf \n
		   |   \r\t    \e\x00 Iñtër\n'
		   6 => 'utf \n\r\t\xab Iñtër'
		   7 => string
		   |  'binary \n
		   |   \r\t    \e\x00 I\xC3\xB1t\xC3\xABr \xA0'
		   8 => 'binary \n\r\t\xab I\xC3\xB1t\xC3\xABr \xA0'
		   'utf \n\r\t\xab Iñtër' => 1
		   'utf \n
		    \r\t    \e\x00 Iñtër' => 2
		   'utf \n
		    \r\t    \e\x00 I\xC3\xB1t\xC3\xABr \xA0' => 3
		   '<div> &amp;' => '<div> &amp;'
		   9 => '\u{FEFF}'
		XX,
	Dumper::toText([
		'',
		' ',
		"\x00",
		"\xFF",
		"I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n", // Iñtërnâtiônàlizætiøn,
		"utf \n\r\t\e\x00 Iñtër\n", // utf + control chars
		'utf \n\r\t\xab Iñtër', // slashes
		"binary \n\r\t\e\x00 Iñtër \xA0", // binary + control chars
		'binary \n\r\t\xab Iñtër ' . "\xA0", // binary + slashes
		'utf \n\r\t\xab Iñtër' => 1, // slashes in key
		"utf \n\r\t\e\x00 Iñtër" => 2, // utf + control chars in key
		"utf \n\r\t\e\x00 Iñtër \xA0" => 3, // binary + control chars in key
		'<div> &amp;' => '<div> &amp;', // HTML
		"\xEF\xBB\xBF", // BOM
	]),
);
