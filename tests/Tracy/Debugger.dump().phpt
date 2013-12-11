<?php

/**
 * Test: Tracy\Debugger::dump() production vs development
 *
 * @author     David Grudl
 */

use Tracy\Debugger,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


header('Content-Type: text/plain');
Tracy\Dumper::$terminalColors = NULL;


test(function() { // production mode
	Debugger::$productionMode = TRUE;

	ob_start();
	Debugger::dump('sensitive data');
	Assert::same( '', ob_get_clean() );

	Assert::match( '"forced" (6)', Debugger::dump('forced', TRUE) );
});


test(function() { // development mode
	Debugger::$productionMode = FALSE;

	ob_start();
	Debugger::dump('sensitive data');
	Assert::match( '"sensitive data" (14)
	', ob_get_clean() );

	Assert::match( '"forced" (6)', Debugger::dump('forced', TRUE) );
});


test(function() { // returned value
	$obj = new stdClass;
	Assert::same( Debugger::dump($obj), $obj );
});
