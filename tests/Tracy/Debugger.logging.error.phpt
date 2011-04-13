<?php

/**
 * Test: Nette\Diagnostics\Debugger error logging.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



// Setup environment
$_SERVER['HTTP_HOST'] = 'nette.org';

Debugger::$logDirectory = __DIR__ . '/log';
TestHelpers::purge(Debugger::$logDirectory);

Debugger::$consoleMode = FALSE;
Debugger::$mailer = 'testMailer';

Debugger::enable(Debugger::PRODUCTION, NULL, 'admin@example.com');

function testMailer() {}

function shutdown() {
	Assert::match('%a%PHP Fatal error: Call to undefined function missing_funcion() in %a%', file_get_contents(Debugger::$logDirectory . '/error.log'));
	Assert::true(is_file(Debugger::$logDirectory . '/email-sent'));
	die(0);
}
Assert::handler('shutdown');



missing_funcion();
