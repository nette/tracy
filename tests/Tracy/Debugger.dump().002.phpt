<?php

/**
 * Test: Nette\Diagnostics\Debugger::dump() with $showLocation.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



header('Content-Type: text/html');
Debugger::$productionMode = FALSE;



ob_start();
dump('hello');

Debugger::$showLocation = TRUE;
Debugger::dump(trim('hello'));
dump('hello');

Assert::match( '<pre title="dump(\'hello\')
in file %a% on line %d%" class="nette-dump"><span class="nette-dump-string">"hello"</span> (5)
</pre>
<pre title="dump(trim(\'hello\'))
in file %a% on line %d%" class="nette-dump"><span class="nette-dump-string">"hello"</span> (5) <small>in %a%:%d%</small>
</pre>
<pre title="dump(\'hello\')
in file %a% on line %d%" class="nette-dump"><span class="nette-dump-string">"hello"</span> (5) <small>in %a%:%d%</small>
</pre>
', ob_get_clean() );
