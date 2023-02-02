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

Debugger::enable(Debugger::Production, $logDirectory, 'admin@example.com');


// throw error
hex2bin('a'); // E_WARNING

Assert::match('%a%PHP Warning: hex2bin(): Hexadecimal input string must have an even length in %a%', file_get_contents($logDirectory . '/error.log'));
Assert::true(is_file($logDirectory . '/email-sent'));
