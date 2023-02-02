<?php

/**
 * Test: Tracy\Debugger error logging.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch %A?%OK!
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


// Setup environment
$_SERVER['HTTP_HOST'] = 'nette.org';

Debugger::$logDirectory = getTempDir();

Debugger::getLogger()->mailer = function () {};

ob_start();
Debugger::enable(Debugger::Production, null, 'admin@example.com');


register_shutdown_function(function () {
	Assert::match('%a%Error: Call to undefined function missing_function() in %a%', file_get_contents(Debugger::$logDirectory . '/exception.log'));
	Assert::true(is_file(Debugger::$logDirectory . '/email-sent'));
	echo 'OK!'; // prevents PHP bug #62725
});


missing_function();
