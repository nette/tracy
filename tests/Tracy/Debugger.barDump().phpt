<?php

/**
 * Test: Tracy\Debugger::barDump()
 * @outputMatch OK!
 */

use Tester\Assert;
use Tester\DomQuery;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


Debugger::$productionMode = false;
header('Content-Type: text/html');
ini_set('session.save_path', TEMP_DIR);
session_start();

ob_start();
Debugger::enable();

register_shutdown_function(function () {
	ob_end_clean();
	$rawContent = reset($_SESSION['_tracy']['bar'])['content'];
	$panelContent = (string) DomQuery::fromHtml($rawContent)->find('#tracy-debug-panel-Tracy-dumps')[0]['data-tracy-content'];
	Assert::matchFile(__DIR__ . '/Debugger.barDump().expect', $panelContent);
	echo 'OK!'; // prevents PHP bug #62725
});


$arr = [10, 20.2, true, false, null, 'hello', ['key1' => 'val1', 'key2' => true], (object) ['key1' => 'val1', 'key2' => true]];

Debugger::barDump($arr);

end($arr)->key1 = 'changed'; // make post-change

Debugger::barDump('<a href="#">test</a>', 'String');
