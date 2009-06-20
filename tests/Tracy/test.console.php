<style>
.dump { color:black; background:white; font-size:12px; text-align:left } .dump span { color: gray }
</style>

<h1>Nette\Debug console test</h1>


<?php
require_once '../../Nette/loader.php';

/*use Nette\Debug;*/

$arr = array(10, 20.2, TRUE, NULL, 'hello', (object) NULL, array());


Debug::dump(get_defined_vars(), Debug::CONSOLE);

Debug::dump($arr, Debug::CONSOLE, 'The Array');

Debug::dump('<a href="#">test</a>', Debug::CONSOLE, 'String');
