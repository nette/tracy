<?php

/**
 * Test: Tracy\Debugger::fireLog()
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('FireLogger is not available in CLI mode');
}


// Setup environment
$_SERVER['HTTP_X_FIRELOGGER'] = TRUE;

Debugger::$productionMode = FALSE;


$arr = [10, 20.2, TRUE, FALSE, NULL, 'hello', ['key1' => 'val1', 'key2' => TRUE], (object) ['key1' => 'val1', 'key2' => TRUE]];

// will show in Firebug "Console" tab
Debugger::fireLog('Hello World'); // Tracy\Debugger::DEBUG
Debugger::fireLog('Info message', Debugger::INFO);
Debugger::fireLog('Warn message', Debugger::WARNING);
Debugger::fireLog('Error message', Debugger::ERROR);
Debugger::fireLog($arr);

Assert::match('%A%
FireLogger-de11e-0:%a%
', implode("\r\n", headers_list()));
