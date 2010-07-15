<?php

/**
 * Test: Nette\Debug logging.
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



function testMailer($message)
{
	T::note("Sending mail with message '$message'");

	global $errorLog;
	foreach (glob(dirname($errorLog) . '/*') as $file) {
		T::note($file);
	}
}



missing_funcion();



__halt_compiler() ?>

------EXPECT------
Sending mail with message 'exception 'FatalErrorException' with message 'Call to undefined function missing_funcion()' in %a%
Stack trace:
#0 [internal function]: %ns%Debug::_shutdownHandler()
#1 {main}

'

%a%/log/exception %a%.html

%a%/log/php_error.log

%a%/log/php_error.log.monitor
