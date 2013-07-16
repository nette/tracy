<?php

/**
 * Test: Tracy\Debugger::barDump() with showLocation.
 *
 * @author     David Grudl
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Helpers::skip();
}


Debugger::$productionMode = FALSE;
Debugger::$showLocation = TRUE;
header('Content-Type: text/html');

Debugger::enable();

register_shutdown_function(function() {
	preg_match('#debug.innerHTML = (".*");#', ob_get_clean(), $m);
	Assert::match(<<<EOD
%A%<h1>Dumped variables</h1>

<div class="nette-inner nette-DumpPanel">

	<table>
			<tr class="">
		<th></th>
		<td><pre class="nette-dump"><span class="nette-dump-string">"value"</span> (5)
</pre>
</td>
	</tr>
		</table>
</div>
%A%
EOD
, json_decode($m[1]));
});
ob_start();


Debugger::barDump('value');
