<?php

/**
 * Test: Tracy\Dumper::dump() modes
 *
 * @author     David Grudl
 * @package    Tracy
 */

use Tracy\Dumper;



require __DIR__ . '/../bootstrap.php';


test(function() { // html mode
	header('Content-Type: text/html');
	ob_start();
	Assert::same( 123, Dumper::dump(123) );
	Assert::match( '<pre class="nette-dump"><span class="nette-dump-number">123</span>
</pre>', ob_get_clean() );
});



test(function() { // text mode
	header('Content-Type: text/plain');
	putenv('TERM=');
	ob_start();
	Assert::same( 123, Dumper::dump(123) );
	Assert::match( '123', ob_get_clean() );
});



test(function() { // terminal mode
	header('Content-Type: text/plain');
	putenv('TERM=xterm-256color');
	ob_start();
	Assert::same( 123, Dumper::dump(123) );
	Assert::match( "\x1b[1;32m123\x1b[0m", ob_get_clean() );
});
