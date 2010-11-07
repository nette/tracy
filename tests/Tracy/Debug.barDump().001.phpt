<?php

/**
 * Test: Nette\Debug::barDump()
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;
header('Content-Type: text/html');

Debug::enable();

function shutdown() {
	$m = Nette\String::match(ob_get_clean(), '#debug.innerHTML = (".*");#');
	Assert::match(file_get_contents(__DIR__ . '/Debug.barDump().001.expect'), json_decode($m[1]));
}
Assert::handler('shutdown');



$arr = array(10, 20.2, TRUE, FALSE, NULL, 'hello', array('key1' => 'val1', 'key2' => TRUE), (object) array('key1' => 'val1', 'key2' => TRUE));

Debug::barDump($arr);

end($arr)->key1 = 'changed'; // make post-change

Debug::barDump('<a href="#">test</a>', 'String');
