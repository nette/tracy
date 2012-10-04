<?php

/**
 * Test: Nette\Diagnostics\Dump::toText() with location
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Dump;



require __DIR__ . '/../bootstrap.php';



Assert::match( '"Hello" (5)
in ' . __FILE__ . ':%d%
', Dump::toText( trim(" Hello "), array("location" => TRUE) ) );
