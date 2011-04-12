<?php

/**
 * Test: Nette\Debug::fireLog()
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



// Setup environment
$_SERVER['HTTP_X_FIRELOGGER'] = TRUE;

Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;



$arr = array(10, 20.2, TRUE, FALSE, NULL, 'hello', array('key1' => 'val1', 'key2' => TRUE), (object) array('key1' => 'val1', 'key2' => TRUE));

// will show in Firebug "Console" tab
Debug::fireLog('Hello World'); // Debug::DEBUG
Debug::fireLog('Info message', Debug::INFO);
Debug::fireLog('Warn message', Debug::WARNING);
Debug::fireLog('Error message', Debug::ERROR);
Debug::fireLog($arr);

Assert::match('%A%
FireLogger-de11e-0:%a%
', implode("\r\n", headers_list()));
