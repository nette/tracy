<?php

/**
 * Test: Tracy\Debugger notices and warnings in HTML.
 * @outputMatch OK!
 */

use Tracy\Debugger;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');
ini_set('session.save_path', TEMP_DIR);

ob_start();
Debugger::enable();

register_shutdown_function(function () {
	$output = ob_get_clean();
	Assert::match('
Warning: Unsupported declare \'foo\' in %a% on line %d%%A%', $output);

	$content = reset(Debugger::getSession()->getContent()['bar'])['content'];
	Assert::match('%A%<table>
<tr>
	<td class="tracy-right">1%a%</td>
	<td><pre>PHP %a%: mktime(): You should be using the time() function instead in %a%:%d%</a></pre></td>
</tr>
<tr>
	<td class="tracy-right">1%a%</td>
	<td><pre>PHP Deprecated: mktime(): %a%</a></pre></td>
</tr>
<tr>
	<td class="tracy-right">1%a%</td>
	<td><pre>PHP Notice: Undefined variable: x in %a%:%d%</a></pre></td>
</tr>
<tr>
	<td class="tracy-right">1%a%</td>
	<td><pre>PHP Warning: %a% in %a%:%d%</a></pre></td>
</tr>
</table>
</div>%A%', $content);
	echo 'OK!'; // prevents PHP bug #62725
});


function first($arg1, $arg2)
{
	second(TRUE, FALSE);
}


function second($arg1, $arg2)
{
	third([1, 2, 3]);
}


function third($arg1)
{
	mktime(); // E_STRICT in PHP 5, E_DEPRECATED in PHP 7
	PHP_MAJOR_VERSION < 7 ? mktime(0, 0, 0, 1, 23, 1978, 1) : mktime(); // E_DEPRECATED
	$x++; // E_NOTICE
	min(1); // E_WARNING
	require 'E_COMPILE_WARNING.php'; // E_COMPILE_WARNING
}


first(10, 'any string');
