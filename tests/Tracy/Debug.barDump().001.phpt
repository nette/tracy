<?php

/**
 * Test: Nette\Debug::barDump()
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;
header('Content-Type: text/html');

Debug::enable();

function shutdown() {
	Assert::match(file_get_contents(__DIR__ . '/Debug.barDump().001.expect'), Nette\String::replace(ob_get_clean(), '#base64Decode\("(.+)"\)#', function($m) { return base64_decode($m[1]); }));
}
Assert::handler('shutdown');



$arr = array(10, 20.2, TRUE, FALSE, NULL, 'hello', array('key1' => 'val1', 'key2' => TRUE), (object) array('key1' => 'val1', 'key2' => TRUE));

Debug::barDump($arr);

end($arr)->key1 = 'changed'; // make post-change

Debug::barDump('<a href="#">test</a>', 'String');
