<?php

/**
 * Test: Nette\Diagnostics\Dumper::toHtml() with location
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Dumper;



require __DIR__ . '/../bootstrap.php';



Assert::match( '<pre class="nette-dump" title="Dumper::toHtml( trim(&quot; Hello &quot;), array(&quot;location&quot; =&gt; TRUE) ) )
in file %a% on line %d%"><span class="nette-dump-string">"Hello"</span> (5)
<small>in <a href="%a%">%a%:%d%</a></small></pre>
', Dumper::toHtml( trim(" Hello "), array("location" => TRUE) ) );
