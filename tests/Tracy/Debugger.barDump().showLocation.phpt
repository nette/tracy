<?php

/**
 * Test: Tracy\Debugger::barDump() with showLocation.
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
%A%<h1>Dumps</h1>

<div class="tracy-inner tracy-DumpPanel">

	<pre class="tracy-dump" title="barDump(&#039;value&#039;)
in file %a% on line %d%" data-tracy-href="editor:%a%"><span class="tracy-dump-string">"value"</span> (5)
<small>in <a href="%a%">%a%:%d%</a></small></pre>
</div>
%A%
EOD
, json_decode($m[1]));
	echo 'OK!'; // prevents PHP bug #62725
});
ob_start();


Debugger::barDump('value');
