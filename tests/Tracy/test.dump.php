<style>
.dump { color:black; background:white; font-size:12px; text-align:left } .dump span { color: gray }
</style>

<h1>Nette\Debug dump test</h1>


<?php
require_once '../../Nette/loader.php';

/*use Nette\Debug;*/


$arr = array(10, 20.2, TRUE, NULL, 'hello');

$obj = (object) array('item1' => $arr, 'item2' => 'hello');


echo '<h2>Check mode</h2>';

Debug::dump(Debug::$html);


echo '<h2>HTML mode</h2>';

Debug::$html = TRUE;

Debug::dump('<a href="#">test</a>');

Debug::dump($arr);

Debug::dump($obj);



echo '<h2>Text mode</h2>';

Debug::$html = FALSE;

Debug::dump('<a href="#">test</a>');

Debug::dump($arr);

Debug::dump($obj);
