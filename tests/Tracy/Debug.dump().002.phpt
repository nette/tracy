<?php

/**
 * Test: Nette\Debug::dump() with $showLocation.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;



Debug::$showLocation = TRUE;

Debug::dump('xxx');



__halt_compiler() ?>

------EXPECT------
<pre class="nette-dump">"xxx" (3) <small>in file %a% on line %d%</small>
</pre>
