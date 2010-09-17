<?php

/**
 * Test: Nette\Debug notices and warnings in HTML.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;
header('Content-Type: text/html');

Debug::enable();

function shutdown() {
	Assert::match('
Warning: Unsupported declare \'foo\' in %a% on line %d%

%A%<div id="nette-debug-errors"><h1>Errors</h1>


<div class="nette-inner">
<table>
<tr class="">
	<td><pre>PHP Strict standards: mktime(): You should be using the time() function instead in %a%:%d%</pre></td>
</tr>
<tr class="nette-alt">
	<td><pre>PHP Deprecated: mktime(): The is_dst parameter is deprecated in %a%:%d%</pre></td>
</tr>
<tr class="">
	<td><pre>PHP Notice: Undefined variable: x in %a%:%d%</pre></td>
</tr>
<tr class="nette-alt">
	<td><pre>PHP Warning: rename(..,..): %A% in %a%:%d%</pre></td>
</tr>
</table>
</div>%A%', Nette\String::replace(ob_get_clean(), '#base64Decode\("(.+)"\)#', function($m) { return base64_decode($m[1]); }));
}
Assert::handler('shutdown');



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
	mktime(); // E_STRICT
	mktime(0, 0, 0, 1, 23, 1978, 1); // E_DEPRECATED
	$x++; // E_NOTICE
	rename('..', '..'); // E_WARNING
	require 'E_COMPILE_WARNING.inc'; // E_COMPILE_WARNING
}


first(10, 'any string');
