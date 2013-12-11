<?php

/**
 * Test: Tracy\Dumper::toHtml() with location
 *
 * @author     David Grudl
 */

use Tracy\Dumper,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::match( '<pre class="tracy-dump" title="Dumper::toHtml( trim(&quot; Hello &quot;), array(&quot;location&quot; =&gt; TRUE) ) )
in file %a% on line %d%"><span class="tracy-dump-string">"Hello"</span> (5)
<small>in <a href="%a%">%a%:%d%</a></small></pre>
', Dumper::toHtml( trim(" Hello "), array("location" => TRUE) ) );
