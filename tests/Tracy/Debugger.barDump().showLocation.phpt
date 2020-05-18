<?php

/**
 * Test: Tracy\Debugger::barDump() with showLocation.
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
Debugger::$showLocation = true;
header('Content-Type: text/html');

ob_start();
Debugger::enable();

register_shutdown_function(function () {
	$output = ob_get_clean();
	preg_match('#Tracy\.Debug\.init\((".*[^\\\\]")\)#', $output, $m);
	$rawContent = json_decode($m[1]);
	$panelContent = (string) DomQuery::fromHtml($rawContent)->find('#tracy-debug-panel-Tracy-dumps')[0]['data-tracy-content'];
	Assert::match(<<<'EOD'
%A%<h1>Dumps</h1>

<div class="tracy-inner tracy-DumpPanel">

	<pre class="tracy-dump--light" title="barDump(&apos;value&apos;)
in file %a% on line %d%
Ctrl-Click to open in editor" data-tracy-href="editor:%a%"><span class="tracy-dump-string" title="5 characters">'value'</span>
<small>in <a href="%a%">%a%:%d%</a></small></pre>
</div>
%A%
EOD
, $panelContent);
	echo 'OK!'; // prevents PHP bug #62725
});


Debugger::barDump('value');
