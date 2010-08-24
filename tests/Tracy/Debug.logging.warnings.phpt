<?php

/**
 * Test: Nette\Debug notices and warnings logging.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



// Setup environment
$_SERVER['HTTP_HOST'] = 'nette.org';

$errorLog = __DIR__ . '/log/php_error.log';
T::purge(dirname($errorLog));

Debug::$consoleMode = FALSE;
Debug::$mailer = 'testMailer';

Debug::enable(Debug::PRODUCTION, $errorLog, 'admin@example.com');

function testMailer() {}


// throw error
$a++;

Assert::match('%a%PHP Notice: Undefined variable: a in %a%', file_get_contents(dirname($errorLog) . '/php_error.log'));
Assert::true(is_file(dirname($errorLog) . '/php_error.log.email-sent'));
