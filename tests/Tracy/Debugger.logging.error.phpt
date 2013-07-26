<?php

/**
 * Test: Tracy\Debugger error logging.
 *
 * @author     David Grudl
 * @exitCode   255
 * @httpCode   500
 * @outputMatch %A%OK!
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


// Setup environment
$_SERVER['HTTP_HOST'] = 'nette.org';

Debugger::$logDirectory = TEMP_DIR;

Debugger::$mailer = function() {};

Debugger::enable(Debugger::PRODUCTION, NULL, 'admin@example.com');


register_shutdown_function(function() {
	Assert::match('%a%Fatal error: Call to undefined function missing_funcion() in %a%', file_get_contents(Debugger::$logDirectory . '/error.log'));
	Assert::true(is_file(Debugger::$logDirectory . '/email-sent'));
	echo 'OK!';
});
ob_start();


missing_funcion();
