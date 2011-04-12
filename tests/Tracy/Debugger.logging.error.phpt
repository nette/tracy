<?php

/**
 * Test: Nette\Debug error logging.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



// Setup environment
$_SERVER['HTTP_HOST'] = 'nette.org';

Debug::$logDirectory = __DIR__ . '/log';
TestHelpers::purge(Debug::$logDirectory);

Debug::$consoleMode = FALSE;
Debug::$mailer = 'testMailer';

Debug::enable(Debug::PRODUCTION, NULL, 'admin@example.com');

function testMailer() {}

function shutdown() {
	Assert::match('%a%PHP Fatal error: Call to undefined function missing_funcion() in %a%', file_get_contents(Debug::$logDirectory . '/error.log'));
	Assert::true(is_file(Debug::$logDirectory . '/email-sent'));
	die(0);
}
Assert::handler('shutdown');



missing_funcion();
