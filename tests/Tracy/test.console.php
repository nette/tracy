<style>
.dump { color:black; background:white; font-size:12px; text-align:left } .dump span { color: gray }
</style>

<h1>Nette\Debug console test</h1>


<?php
require_once '../../Nette/loader.php';

/*use Nette\Debug;*/

$_ENV = NULL;
$_SERVER = array_intersect_key($_SERVER, array('PHP_SELF' => 1, 'SCRIPT_NAME' => 1, 'SERVER_ADDR' => 1, 'SERVER_SOFTWARE' => 1, 'HTTP_HOST' => 1, 'DOCUMENT_ROOT' => 1));

$arr = array(10, 20.2, TRUE, NULL, 'hello', (object) NULL, array());


Debug::consoleDump(get_defined_vars());

Debug::consoleDump($arr, 'The Array');

Debug::consoleDump('<a href="#">test</a>', 'String');
