<?php

/**
 * Test: Tracy\Debugger error logging.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch %A?%OK!
 */

use Tracy\Debugger;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// Setup environment
$_SERVER['HTTP_HOST'] = 'nette.org';

Debugger::$logDirectory = TEMP_DIR;

Debugger::getLogger()->mailer = function () {};

Debugger::enable(Debugger::PRODUCTION, NULL, 'admin@example.com');


register_shutdown_function(function () {
	Assert::match('%a%Fatal error: Call to undefined function missing_function() in %a%', file_get_contents(Debugger::$logDirectory . '/exception.log'));
	Assert::true(is_file(Debugger::$logDirectory . '/email-sent'));
	echo 'OK!'; // prevents PHP bug #62725
});
ob_start();


missing_function();
