<?php

/**
 * Test: Nette\Diagnostics\Dump::toHtml() with location
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Dump;



require __DIR__ . '/../bootstrap.php';



Assert::match( '<pre class="nette-dump" title="Dump::toHtml( trim(&quot; Hello &quot;), array(&quot;location&quot; =&gt; TRUE) ) )
in file %a% on line %d%"><span class="nette-dump-string">"Hello"</span> (5)
<small>in <a href="%a%">%a%:%d%</a></small></pre>
', Dump::toHtml( trim(" Hello "), array("location" => TRUE) ) );
