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

Debugger::getLogger()->addReportChannel(function () {});

ob_start();
Debugger::enable(Debugger::PRODUCTION);


register_shutdown_function(function () {
	Assert::match('%a%Error: Call to undefined function missing_function() in %a%', file_get_contents(Debugger::$logDirectory . '/exception.log'));
	Assert::true(is_file(Debugger::$logDirectory . '/email-sent'));
	echo 'OK!'; // prevents PHP bug #62725
});


missing_function();
