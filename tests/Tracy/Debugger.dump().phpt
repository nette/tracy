<?php

/**
 * Test: Nette\Diagnostics\Debugger::dump() production vs development
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';


header('Content-Type: text/plain');


// production mode
Debugger::$productionMode = TRUE;

ob_start();
Debugger::dump('sensitive data');
Assert::same( '', ob_get_clean() );

Assert::match( '"forced" (6)', Debugger::dump('forced', TRUE) );


// development mode
Debugger::$productionMode = FALSE;

ob_start();
Debugger::dump('sensitive data');
Assert::match( '"sensitive data" (14)
', ob_get_clean() );

Assert::match( '"forced" (6)', Debugger::dump('forced', TRUE) );


// returned value
$obj = new stdClass;
Assert::same( Debugger::dump($obj), $obj );
