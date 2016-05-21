<?php

/**
 * Test: Tracy\Debugger::barDump() with showLocation.
 * @outputMatch OK!
 */

use Tester\Assert;
use Tester\DomQuery;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


Debugger::$productionMode = FALSE;
Debugger::$showLocation = TRUE;
header('Content-Type: text/html');
ini_set('session.save_path', TEMP_DIR);
session_start();

ob_start();
Debugger::enable();

register_shutdown_function(function () {
	ob_end_clean();
	$rawContent = reset($_SESSION['_tracy']['bar'])['content'];
	$panelContent = (string) DomQuery::fromHtml($rawContent)->find('#tracy-debug-panel-Tracy-dumps')[0]['data-tracy-content'];
	Assert::match(<<<EOD
%A%<h1>Dumps</h1>

<div class="tracy-inner tracy-DumpPanel">

	<pre class="tracy-dump" title="barDump(&#039;value&#039;)
in file %a% on line %d%" data-tracy-href="editor:%a%"><span class="tracy-dump-string">"value"</span> (5)
<small>in <a href="%a%">%a%:%d%</a></small></pre>
</div>
%A%
EOD
, $panelContent);
	echo 'OK!'; // prevents PHP bug #62725
});


Debugger::barDump('value');
