<?php

/**
 * Test: Nette\Debug::dump() with $showLocation.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;



Debug::$showLocation = TRUE;

Debug::dump('xxx');



__halt_compiler();

------EXPECT------
<pre class="dump"><span>string</span>(3) "xxx" <small>in file %a%</small>
</pre>
