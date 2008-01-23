<h1>Nette::Debug timer test</h1>


<?php
require_once '../../Nette/Debug.php';

/*use Nette::Debug;*/

Debug::timer();

sleep(2);

echo round(Debug::timer(), 2);
