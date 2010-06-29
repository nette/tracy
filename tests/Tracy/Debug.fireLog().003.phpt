<?php

/**
 * Test: Nette\Debug::fireLog() in production mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



// Setup environment
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Gecko/2008070208 Firefox/3.0.1 FirePHP/0.1.0.3';

Debug::$consoleMode = FALSE;
Debug::$productionMode = TRUE;


Debug::fireLog('Sensitive log');

flush();

T::dump( headers_list() );



__halt_compiler() ?>

------EXPECT------
array(2) {
	0 => string(29) "X-Powered-By: Nette Framework"
	1 => string(39) "Content-Type: %a%"
}
