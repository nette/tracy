<?php

/**
 * Test: Nette\Debug logging.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



// Setup environment
$_SERVER['HTTP_HOST'] = 'nettephp.com';

$errorLog = dirname(__FILE__) . '/log/php_error.log';
NetteTestHelpers::purge(dirname($errorLog));

Debug::$consoleMode = FALSE;
Debug::$mailer = 'testMailer';

Debug::enable(Debug::PRODUCTION, $errorLog, 'admin@example.com');



function testMailer($message)
{
	output("Sending mail with message '$message'");

	global $errorLog;
	foreach (glob(dirname($errorLog) . '/*') as $file) {
		output($file);
	}
}



missing_funcion();



__halt_compiler();

------EXPECT------
Sending mail with message 'exception 'FatalErrorException' with message 'Call to undefined function missing_funcion()' in %a%
Stack trace:
#0 [internal function]: %ns%Debug::shutdownHandler()
#1 {main}'

%a%/log/exception %a%.html

%a%/log/php_error.log

%a%/log/php_error.log.monitor
