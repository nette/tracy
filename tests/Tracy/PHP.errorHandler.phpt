<?php

/**
 * Test: PHP error handler.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */



require __DIR__ . '/../bootstrap.php';


// ensure trigger_error works as expected
set_error_handler(function($severity, $message, $file, $line, $context) {
	Assert::same( 10, $context['var'] );
});

$var = 10;
trigger_error('Ahoj', E_USER_ERROR);
