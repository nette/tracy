<?php

/**
 * Test: Nette\Debug::dump() in production mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = TRUE;


ob_start();
Debug::dump('sensitive data');
Assert::same( '', ob_get_clean() );

Assert::match( '<pre class="nette-dump">"forced" (6)
</pre>', Debug::dump('forced', TRUE) );
