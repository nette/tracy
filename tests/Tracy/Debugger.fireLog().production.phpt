<?php

/**
 * Test: Tracy\Debugger::fireLog() in production mode.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


// Setup environment
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Gecko/2008070208 Firefox/3.0.1 FirePHP/0.1.0.3';

Debugger::$productionMode = true;


Debugger::fireLog('Sensitive log');

flush();

Assert::false(strpos(implode('', headers_list()), 'X-Wf-'));
