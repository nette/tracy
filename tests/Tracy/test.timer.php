<h1>Nette\Debug timer test</h1>


<?php
require_once '../../Nette/loader.php';

/*use Nette\Debug;*/

Debug::timer();

sleep(1);

Debug::timer('foo');

sleep(1);

Debug::dump( round(Debug::timer(), 1) );

Debug::dump( round(Debug::timer('foo'), 1) );
