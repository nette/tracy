<style>
.dump { color:black; background:white; font-size:12px; text-align:left } .dump span { color: gray }
</style>

<h1>Nette\Debug dump test</h1>


<?php
require_once '../../Nette/loader.php';

/*use Nette\Debug;*/


$arr = array(10, 20.2, TRUE, NULL, 'hello');

$obj = (object) array('item1' => $arr, 'item2' => 'hello');


echo "<h2>Check mode</h2>\n";

Debug::dump(Debug::$consoleMode ? 'console' : 'html');


echo "<h2>HTML mode</h2>\n";

Debug::$consoleMode = FALSE;

Debug::dump('<a href="#">test</a>');

Debug::dump($arr);

Debug::dump($obj);


echo "<h2>Text mode</h2>\n";

Debug::$consoleMode = TRUE;

Debug::dump('<a href="#">test</a>');

Debug::dump($arr);

Debug::dump($obj);


echo "<h2>With location</h2>\n";

Debug::$showLocation = TRUE;

Debug::dump('sensitive data');

echo Debug::dump('forced', TRUE);

Debug::$showLocation = FALSE;
Debug::$consoleMode = FALSE;


echo "<h2>Production mode</h2>\n";

Debug::$productionMode = TRUE;

Debug::dump('sensitive data');

echo Debug::dump('forced', TRUE);


echo "<h2>Development mode</h2>\n";

Debug::$productionMode = FALSE;

Debug::dump('sensitive data');

echo Debug::dump('forced', TRUE);


echo "<h2>With location</h2>\n";

Debug::$showLocation = TRUE;

Debug::dump('sensitive data');

echo Debug::dump('forced', TRUE);
