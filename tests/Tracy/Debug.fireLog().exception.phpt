<?php

/**
 * Test: Nette\Debug::fireLog() and exception.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



// Setup environment
$_SERVER['HTTP_X_FIRELOGGER'] = TRUE;

Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;



function first($arg1, $arg2)
{
	second(TRUE, FALSE);
}



function second($arg1, $arg2)
{
	third(array(1, 2, 3));
}


function third($arg1)
{
	throw new Exception('The my exception', 123);
}

try {
	first(10, 'any string');

} catch (Exception $e) {
	Debug::fireLog($e);
}


Assert::match('%A%
FireLogger-de11e-0:eyJsb2dzIjpbeyJuYW1lIjoiUEhQIiwibGV2ZWwiOiJkZWJ1ZyIsIm9yZGVyIjowLCJ0aW1lIjoiMDAwMDAwLjUgbXMiLCJ0ZW1wbGF0ZSI6IkV4Y2VwdGlvbjogVGhlIG15IGV4Y2VwdGlvbiAjMTIzIGluIFx1MjAyNlxcdGVzdHNcXERlYnVnXFxEZWJ1Zy5maXJlTG9nKCkuZXhjZXB0aW9uLnBocHQ6NDIiLCJtZXNzYWdlIjoiIiwic3R5bGUiOiJiYWNrZ3JvdW5kOiM3NjdhYjYiLCJleGNfaW5mbyI6WyJUaGUgbXkgZXhjZXB0aW9uIiwiVzpcXE5ldHRlXFxfbmV0dGVcXHRlc3RzXFxEZWJ1Z1xcRGVidWcuZmlyZUxvZygpLmV4Y2VwdGlvbi5waHB0IixbWyJXOlxcTmV0dGVcXF9uZXR0ZVxcdGVzdHNcXERlYnVnXFxEZWJ1Zy5maXJlTG9nKCkuZXhjZXB0aW9uLnBocHQiLDM2LCJ0aGlyZCIsbnVsbF0sWyJXOlxcTmV0dGVcXF9uZXR0ZVxcdGVzdHNcXERlYnVnXFxEZWJ1Zy5maXJlTG9nKCkuZXhjZXB0aW9uLnBocHQiLDI5LCJzZWNvbmQiLG51bGxdLFsiVzpcXE5ldHRlXFxfbmV0dGVcXHRlc3RzXFxEZWJ1Z1xcRGVidWcuZmlyZUxvZygpLmV4Y2VwdGlvbi5waHB0Iiw0NiwiZmlyc3QiLG51bGxdXV0sImV4Y19mcmFtZXMiOltbWzEsMiwzXV0sW3RydWUsZmFsc2VdLFsxMCwiYW55IHN0cmluZyJdXSwiYXJncyI6W10sInBhdGhuYW1lIjoiVzpcXE5ldHRlXFxfbmV0dGVcXHRlc3RzXFxEZWJ1Z1xcRGVidWcuZmlyZUxvZygpLmV4Y2VwdGlvbi5waHB0IiwibGluZW5vIjo0Mn1dfQ==
', implode("\r\n", headers_list()));
