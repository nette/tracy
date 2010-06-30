<?php

/**
 * Test: Nette\Debug::barDump() with showLocation.
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

Debug::enable();

header('Content-Type: text/html');

Debug::barDump('value');



__halt_compiler() ?>

------EXPECT------
%A%<h1>Dumped variables</h1> <div class="nette-inner"> <table> <tr class=""> <th></th> <td><pre class="nette-dump"><span>string</span>(5) "value"
</pre> </td> </tr> </table> </div> </div>%A%
