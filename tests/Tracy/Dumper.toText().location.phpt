<?php

/**
 * Test: Nette\Diagnostics\Dumper::toText() with location
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Dumper;



require __DIR__ . '/../bootstrap.php';



Assert::match( '"Hello" (5)
in ' . __FILE__ . ':%d%
', Dumper::toText( trim(" Hello "), array("location" => TRUE) ) );
