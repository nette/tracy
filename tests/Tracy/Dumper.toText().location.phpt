<?php

/**
 * Test: Tracy\Dumper::toText() with location
 *
 * @author     David Grudl
 */

use Tracy\Dumper,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::match( '"Hello" (5)
in %a%:%d%
', Dumper::toText( trim(" Hello "), array("location" => TRUE) ) );
