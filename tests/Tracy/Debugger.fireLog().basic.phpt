<?php

/**
 * Test: Tracy\Debugger::fireLog()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('FireLogger is not available in CLI mode');
}


// Setup environment
$_SERVER['HTTP_X_FIRELOGGER'] = true;

Debugger::$productionMode = false;


$arr = [10, 20.2, true, false, null, 'hello', ['key1' => 'val1', 'key2' => true], (object) ['key1' => 'val1', 'key2' => true]];

// will show in FireLogger
Debugger::fireLog('Hello World'); // Tracy\Debugger::DEBUG
Debugger::fireLog('Info message', Debugger::INFO);
Debugger::fireLog('Warn message', Debugger::WARNING);
Debugger::fireLog('Error message', Debugger::ERROR);
Debugger::fireLog($arr);

preg_match('#^FireLogger-de11e-0:(.+)#m', implode("\n", headers_list()), $matches);
Assert::true(isset($matches[1]));
