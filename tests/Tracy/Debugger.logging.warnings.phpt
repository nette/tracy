<?php

/**
 * Test: Tracy\Debugger notices and warnings logging.
 *
 * @author     David Grudl
 * @package    Tracy
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


$contents = trim(@file_get_contents($logDirectory . '/error.log'));

Assert::match('PHP Notice: Undefined variable: a in %a%', $contents);
Assert::true(is_file($logDirectory . '/email-sent-' . md5($contents)));
