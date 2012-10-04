<?php

/**
 * Test: Nette\Diagnostics\Dump::toText() locale
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Dump;



require __DIR__ . '/../bootstrap.php';



setLocale(LC_ALL, 'czech');

Assert::match( 'array (2) [
   0 => -10.0
   1 => 10.3
]

', Dump::toText(array(-10.0, 10.3)));
