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

ob_start();
register_shutdown_function(function() {
	Assert::match(<<<EOD
%A%<h1>Dumped variables</h1> <div class="nette-inner"> <table> <tr class=""> <th></th> <td><pre class="nette-dump">"value" (5)
</pre> </td> </tr> </table> </div> </div>%A%
EOD
, ob_get_clean());
});
