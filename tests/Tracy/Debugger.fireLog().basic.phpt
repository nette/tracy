<?php

/**
 * Test: Tracy\Debugger::fireLog()
 *
 * @author     David Grudl
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('FireLogger is not available in CLI mode');
}


// Setup environment
$_SERVER['HTTP_X_FIRELOGGER'] = TRUE;

Debugger::$productionMode = FALSE;


$arr = array(10, 20.2, TRUE, FALSE, NULL, 'hello', array('key1' => 'val1', 'key2' => TRUE), (object) array('key1' => 'val1', 'key2' => TRUE));

// will show in Firebug "Console" tab
Debugger::fireLog('Hello World'); // Tracy\Debugger::DEBUG
Debugger::fireLog('Info message', Debugger::INFO);
Debugger::fireLog('Warn message', Debugger::WARNING);
Debugger::fireLog('Error message', Debugger::ERROR);
Debugger::fireLog($arr);

Assert::match('%A%
FireLogger-de11e-0:%a%
', implode("\r\n", headers_list()));
