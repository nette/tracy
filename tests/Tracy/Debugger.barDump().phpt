<?php

/**
 * Test: Tracy\Debugger::barDump()
 * @outputMatch OK!
 */

use Tracy\Debugger;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


Debugger::$productionMode = FALSE;
header('Content-Type: text/html');
ini_set('session.save_path', TEMP_DIR);

ob_start();
Debugger::enable();

register_shutdown_function(function () {
	ob_end_clean();
	$content = reset(Debugger::getSession()->getContent()['bar'])['content'];
	Assert::matchFile(__DIR__ . '/Debugger.barDump().expect', $content);
	echo 'OK!'; // prevents PHP bug #62725
});


$arr = [10, 20.2, TRUE, FALSE, NULL, 'hello', ['key1' => 'val1', 'key2' => TRUE], (object) ['key1' => 'val1', 'key2' => TRUE]];

Debugger::barDump($arr);

end($arr)->key1 = 'changed'; // make post-change

Debugger::barDump('<a href="#">test</a>', 'String');
