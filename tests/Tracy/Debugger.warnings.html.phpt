<?php

/**
 * Test: Tracy\Debugger notices and warnings in HTML.
 * @outputMatch OK!
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

register_shutdown_function(function() {
	preg_match('#debug.innerHTML = (".*");#', $output = ob_get_clean(), $m);
	Assert::match('
Warning: Unsupported declare \'foo\' in %a% on line %d%%A%', $output);

	Assert::match('%A%<table>
<tr class="">
	<td class="tracy-right">1%a%</td>
	<td><pre>PHP Strict standards: mktime(): You should be using the time() function instead in %a%:%d%</a></pre></td>
</tr>
<tr class="tracy-alt">
	<td class="tracy-right">1%a%</td>
	<td><pre>PHP Deprecated: mktime(): The is_dst parameter is deprecated in %a%:%d%</a></pre></td>
</tr>
<tr class="">
	<td class="tracy-right">1%a%</td>
	<td><pre>PHP Notice: Undefined variable: x in %a%:%d%</a></pre></td>
</tr>
<tr class="tracy-alt">
	<td class="tracy-right">1%a%</td>
	<td><pre>PHP Warning: %a% in %a%:%d%</a></pre></td>
</tr>
</table>
</div>%A%', json_decode($m[1]));
	echo 'OK!'; // prevents PHP bug #62725
});
ob_start();


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
	min(1); // E_WARNING
	require 'E_COMPILE_WARNING.inc'; // E_COMPILE_WARNING
}


first(10, 'any string');
