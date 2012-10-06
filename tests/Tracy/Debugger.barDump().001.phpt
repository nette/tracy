<?php

/**
 * Test: Nette\Diagnostics\Debugger::barDump()
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Debugger,
	Nette\StringUtils;



require __DIR__ . '/../bootstrap.php';



Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

function shutdown() {
	$m = StringUtils::match(ob_get_clean(), '#debug.innerHTML = (".*");#');
	Assert::match(file_get_contents(__DIR__ . '/Debugger.barDump().001.expect'), json_decode($m[1]));
}
ob_start();
Debugger::$onFatalError[] = 'shutdown';


$arr = array(10, 20.2, TRUE, FALSE, NULL, 'hello', array('key1' => 'val1', 'key2' => TRUE), (object) array('key1' => 'val1', 'key2' => TRUE));

Debugger::barDump($arr);

end($arr)->key1 = 'changed'; // make post-change

Debugger::barDump('<a href="#">test</a>', 'String');
