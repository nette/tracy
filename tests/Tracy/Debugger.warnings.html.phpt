<?php

/**
 * Test: Tracy\Debugger notices and warnings in HTML.
 * @outputMatch OK!
 */

declare(strict_types=1);

use Tester\Assert;
use Tester\DomQuery;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


Debugger::$productionMode = false;
header('Content-Type: text/html');

ob_start();
Debugger::enable();

register_shutdown_function(function () {
	$output = ob_get_clean();
	Assert::match('
Warning: Unsupported declare \'foo\' in %a% on line %d%%A%', $output);

	preg_match('#Tracy\.Debug\.init\((".*[^\\\\]")\)#', $output, $m);
	$rawContent = json_decode($m[1]);
	$panelContent = (string) DomQuery::fromHtml($rawContent)->find('#tracy-debug-panel-Tracy-errors')[0]['data-tracy-content'];
	Assert::match(<<<'XX'
%A%<table class="tracy-sortable">
<tr>
	<td class="tracy-right">1%a%</td>
	<td><pre>PHP Notice: Only variables should be assigned by reference in %a%:%d%</a></pre></td>
</tr>
<tr>
	<td class="tracy-right">1%a%</td>
	<td><pre>PHP Warning: hex2bin(): Hexadecimal input string must have an even length in %a%:%d%</a></pre></td>
</tr>
<tr>
	<td class="tracy-right">1%a%</td>
	<td><pre>PHP Compile Warning: Unsupported declare &#039;foo&#039; in %a%:%d%</a></pre></td>
</tr>
</table>
</div>%A%
XX
, $panelContent);
	echo 'OK!'; // prevents PHP bug #62725
});


function first($arg1, $arg2)
{
	second(true, false);
}


function second($arg1, $arg2)
{
	third([1, 2, 3]);
}


function third($arg1)
{
	$x = &pi(); // E_NOTICE
	hex2bin('a'); // E_WARNING
	require __DIR__ . '/fixtures/E_COMPILE_WARNING.php'; // E_COMPILE_WARNING
}


first(10, 'any string');
