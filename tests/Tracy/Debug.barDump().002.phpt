<?php

/**
 * Test: Nette\Debug::barDump() with showLocation.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;
Debug::$showLocation = TRUE;
header('Content-Type: text/html');

Debug::enable();

function shutdown() {
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
, Nette\String::replace(ob_get_clean(), '#base64Decode\("(.+)"\)#', function($m) { return base64_decode($m[1]); }));
}
Assert::handler('shutdown');



Debug::barDump('value');
