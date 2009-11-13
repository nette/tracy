<?php

/**
 * Test: Nette\Debug::consoleDump() with showLocation.
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

header('Content-Type: text/html');

Debug::consoleDump('value');



__halt_compiler();

------EXPECT------


<script type="text/javascript">
%A%
_netteConsole.document.body.innerHTML = _netteConsole.document.body.innerHTML + "\t\r\n\t<table>\r\n\t\t\t<tr class=\"even\">\r\n\t\t<th><\/th>\r\n\t\t<td><pre class=\"dump\"><span>string<\/span>(5) \"value\" <small>in file %a% on line %d%<\/small>\n<\/pre>\n<\/td>\r\n\t<\/tr>\r\n\t\t<\/table>\r\n";
%A%
</script>
