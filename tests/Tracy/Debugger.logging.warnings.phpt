<?php

/**
 * Test: Tracy\Debugger notices and warnings logging.
 *
 * @author     David Grudl
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


// Setup environment
$_SERVER['HTTP_HOST'] = 'nette.org';

$logDirectory = TEMP_DIR;

Debugger::$mailer = 'testMailer';

Debugger::enable(Debugger::PRODUCTION, $logDirectory, 'admin@example.com');

function testMailer() {}


// throw error
$a++;

Assert::match('%a%PHP Notice: Undefined variable: a in %a%', file_get_contents($logDirectory . '/error.log'));
Assert::true(is_file($logDirectory . '/email-sent'));
