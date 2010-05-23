<?php

/**
 * Test: Nette\Debug::fireLog() in production mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



// Setup environment
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Gecko/2008070208 Firefox/3.0.1 FirePHP/0.1.0.3';

Debug::$consoleMode = FALSE;
Debug::$productionMode = TRUE;


Debug::fireLog('Sensitive log');

flush();

dump( headers_list() );



__halt_compiler() ?>

------EXPECT------
array(1) {
	0 => string(39) "Content-Type: %a%"
}
