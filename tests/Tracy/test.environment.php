<pre>
<?php

require_once '../../Nette/loader.php';

/*use Nette\Debug;*/

Debug::$time = 1201042800;
$_SERVER = array_intersect_key($_SERVER, array('PHP_SELF' => 1, 'SCRIPT_NAME' => 1, 'SERVER_ADDR' => 1, 'SERVER_SOFTWARE' => 1, 'HTTP_HOST' => 1, 'DOCUMENT_ROOT' => 1));

echo "Debug::\$productionMode\n";
Debug::dump( Debug::$productionMode );

echo "setting production environment...\n";

Environment::setMode('production', TRUE);
Debug::enable();

echo "Debug::\$productionMode\n";
echo Debug::dump(Debug::$productionMode, TRUE);
