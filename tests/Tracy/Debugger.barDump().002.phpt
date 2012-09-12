<?php

/**
 * Test: Nette\Diagnostics\Debugger::barDump() with showLocation.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Debugger,
	Nette\StringUtils;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = FALSE;
Debugger::$productionMode = FALSE;
Debugger::$showLocation = TRUE;
header('Content-Type: text/html');

Debugger::enable();

function shutdown() {
	$m = StringUtils::match(ob_get_clean(), '#debug.innerHTML = (".*");#');
	Assert::match(<<<EOD
%A%<h1>Dumped variables</h1>

<div class="nette-inner">

	<table>
			<tr class="">
		<th></th>
		<td><pre class="nette-dump">"value" (5)
</pre></td>
	</tr>
		</table>
</div>
%A%
EOD
, json_decode($m[1]));
}
ob_start();
Debugger::$onFatalError[] = 'shutdown';


Debugger::barDump('value');
