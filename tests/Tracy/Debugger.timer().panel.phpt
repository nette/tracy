<?php

/**
 * Test: Tracy\Debugger::timer() panel
 * @outputMatch OK!
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


Debugger::$productionMode = FALSE;
Debugger::$showLocation = TRUE;
header('Content-Type: text/html');

Debugger::enable();

register_shutdown_function(function() {
	preg_match('#debug.innerHTML = (".*");#', ob_get_clean(), $m);
	Assert::match(<<<EOD
%A%<h1>Times: 2</h1>
<div class="tracy-inner tracy-timePanel">
<table>
	<tr>
		<th>Name</th>
		<th>Since start</th>
		<th>Since last</th>
		<th title="Since last with same name">Delta</th>
	</tr>
	<tr>
		<th></th>
		<td style="text-align: right;">%d%.%d%&nbsp;ms</td>
		<td style="text-align: right;">%d%.%d%&nbsp;ms</td>
		<td style="text-align: right;">%d%.%d%&nbsp;ms</td>
	</tr>
	<tr>
		<th>foo</th>
		<td style="text-align: right;">%d%.%d%&nbsp;ms</td>
		<td style="text-align: right;">%d%.%d%&nbsp;ms</td>
		<td style="text-align: right;">%d%.%d%&nbsp;ms</td>
	</tr>
</table>
</div>
%A%
EOD
, json_decode($m[1]));
	echo 'OK!'; // prevents PHP bug #62725
});
ob_start();


Debugger::timer();

Debugger::timer('foo');
