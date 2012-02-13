<?php

/**
 * Test: Nette\Diagnostics\Debugger::dump() and strings.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleColors = NULL;
Debugger::$consoleMode = TRUE;
Debugger::$productionMode = FALSE;



Assert::match( 'array(8) [
   0 => ""
   1 => " "
   2 => "	"
   3 => "single line" (11)
   4 => "multi
line" (10)
   5 => "Iñtërnâtiônàlizætiøn" (27)
   6 => "\x00"
   7 => "\xff"
]

', Debugger::dump(array(
	'',
	' ',
	"\t",
	"single line",
	"multi\nline",
	"I\xc3\xb1t\xc3\xabrn\xc3\xa2ti\xc3\xb4n\xc3\xa0liz\xc3\xa6ti\xc3\xb8n", // Iñtërnâtiônàlizætiøn,
	"\x00",
	"\xFF",
), TRUE));
