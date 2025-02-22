<?php

/**
 * Test: Tracy\Debugger::barDump()
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
setHtmlMode();

ob_start();
Debugger::enable();

register_shutdown_function(function () {
	$output = ob_get_clean();
	preg_match('#Tracy\.Debug\.init\((".*[^\\\]")\)#', $output, $m);
	$rawContent = str_replace('<\!--', '<!--', $m[1], $count);
	$rawContent = json_decode($rawContent);
	$panelContent = (string) DomQuery::fromHtml($rawContent)->find('#tracy-debug-panel-Tracy-dumps')[0]['data-tracy-content'];
	Assert::matchFile(__DIR__ . '/expected/Debugger.barDump().expect', $panelContent);
	echo 'OK!'; // prevents PHP bug #62725
});


$arr = [10, 20.2, true, false, null, 'hello <!-- <script> </script>', ['key1' => 'val1', 'key2' => true], (object) ['key1' => 'val1', 'key2' => true]];

Debugger::barDump($arr);

end($arr)->key1 = 'changed'; // make post-change

Debugger::barDump('<a href="#">test</a>', 'String');
