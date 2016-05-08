<?php

/**
 * Test: Tracy\Debugger notices and warnings logging.
 */

use Tracy\Debugger;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// Setup environment
$_SERVER['HTTP_HOST'] = 'nette.org';

$logDirectory = TEMP_DIR;

Debugger::getLogger()->addReportChannel(function () {});

Debugger::enable(Debugger::PRODUCTION, $logDirectory);


// throw error
$a++;

Assert::match('%a%PHP Notice: Undefined variable: a in %a%', file_get_contents($logDirectory . '/error.log'));
Assert::true(is_file($logDirectory . '/email-sent'));
