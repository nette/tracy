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



require __DIR__ . '/../NetteTest/initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = TRUE;



Debug::dump('sensitive data');

echo Debug::dump('forced', TRUE);



__halt_compiler() ?>

------EXPECT------
<pre class="nette-dump"><span>string</span>(6) "forced"
</pre>
