<?php

/**
 * Test: Tracy\Debugger error logging.
 *
 * @author     David Grudl
 * @package    Tracy
 */


use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

// Setup environment
$_SERVER['HTTP_HOST'] = 'nette.org';

Debugger::$logDirectory = TEMP_DIR;

Debugger::$mailer = 'testMailer';

Debugger::enable(Debugger::PRODUCTION, NULL, 'admin@example.com');

function testMailer() {}

Debugger::$onFatalError[] = function() {
	$contents = trim(@file_get_contents(Debugger::$logDirectory . '/error.log'));

	Assert::match('Fatal error: Call to undefined function missing_funcion() in %a%', $contents);
	Assert::true(is_file(Debugger::$logDirectory . '/email-sent-' . md5($contents)));

	die(0);
};
ob_start();


missing_funcion();
