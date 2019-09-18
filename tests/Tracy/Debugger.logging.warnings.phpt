<?php

/**
 * Test: Tracy\Debugger notices and warnings logging.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


// Setup environment
$_SERVER['HTTP_HOST'] = 'nette.org';

$logDirectory = getTempDir();

Debugger::getLogger()->mailer = function () {};

Debugger::enable(Debugger::PRODUCTION, $logDirectory, 'admin@example.com');


// throw error
$a++;

Assert::match('%a%PHP Notice: Undefined variable: a in %a%', file_get_contents($logDirectory . '/error.log'));
Assert::true(is_file($logDirectory . '/email-sent'));
