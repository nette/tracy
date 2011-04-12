<?php

/**
 * Test: Nette\Debug::barDump() with showLocation.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;
Debug::$showLocation = TRUE;
header('Content-Type: text/html');

Debug::enable();

function shutdown() {
	$m = Nette\String::match(ob_get_clean(), '#debug.innerHTML = (".*");#');
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
Assert::handler('shutdown');



Debug::barDump('value');
