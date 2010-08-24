<?php

/**
 * Test: Nette\Debug::barDump()
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
header('Content-Type: text/html');

Debug::enable();

function shutdown() {
	Assert::match(<<<EOD
%A%<h1>Dumped variables</h1> <div class="nette-inner"> <table> <tr class=""> <th>0</th> <td><pre class="nette-dump">10
</pre> </td> </tr> <tr class="nette-alt"> <th>1</th> <td><pre class="nette-dump">20.2
</pre> </td> </tr> <tr class=""> <th>2</th> <td><pre class="nette-dump">TRUE
</pre> </td> </tr> <tr class="nette-alt"> <th>3</th> <td><pre class="nette-dump">FALSE
</pre> </td> </tr> <tr class=""> <th>4</th> <td><pre class="nette-dump">NULL
</pre> </td> </tr> <tr class="nette-alt"> <th>5</th> <td><pre class="nette-dump">"hello" (5)
</pre> </td> </tr> <tr class=""> <th>6</th> <td><pre class="nette-dump"><a href='#' class='nette-toggler'><span>array</span>(2) <abbr>&#x25bc;</abbr> </a><code>{
   "key1" => "val1" (4)
   "key2" => TRUE
}</code>
</pre> </td> </tr> <tr class="nette-alt"> <th>7</th> <td><pre class="nette-dump"><a href='#' class='nette-toggler'><span>stdClass</span>(2) <abbr>&#x25bc;</abbr> </a><code>{
   "key1" => "val1" (4)
   "key2" => TRUE
}</code>
</pre> </td> </tr> </table> <h2>String</h2> <table> <tr class=""> <th></th> <td><pre class="nette-dump">"&lt;a href="#"&gt;test&lt;/a&gt;" (20)
</pre> </td> </tr> </table> </div> </div>%A%
EOD
, ob_get_clean());
}
Assert::handler('shutdown');



$arr = array(10, 20.2, TRUE, FALSE, NULL, 'hello', array('key1' => 'val1', 'key2' => TRUE), (object) array('key1' => 'val1', 'key2' => TRUE));

Debug::barDump($arr);

end($arr)->key1 = 'changed'; // make post-change

Debug::barDump('<a href="#">test</a>', 'String');
