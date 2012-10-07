<?php

/**
 * Test: Nette\Diagnostics\Dump::toHtml()
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Dump;



require __DIR__ . '/../bootstrap.php';



class Test
{
	public $x = array(10, NULL);

	private $y = 'hello';

	protected $z = 30.0;
}


Assert::match( '<pre class="nette-dump"><span class="nette-dump-null">NULL</span>
</pre>', Dump::toHtml(NULL) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-bool">TRUE</span>
</pre>', Dump::toHtml(TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-bool">FALSE</span>
</pre>', Dump::toHtml(FALSE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-number">0</span>
</pre>', Dump::toHtml(0) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-number">1</span>
</pre>', Dump::toHtml(1) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-number">0.0</span>
</pre>', Dump::toHtml(0.0) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-number">0.1</span>
</pre>', Dump::toHtml(0.1) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-string">""</span>
</pre>', Dump::toHtml('') );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-string">"0"</span>
</pre>', Dump::toHtml('0') );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-string">"\\x00"</span>
</pre>', Dump::toHtml("\x00") );

Assert::match( '<pre class="nette-dump"><span class="nette-toggle"><span class="nette-dump-array">array</span> (5)</span>
<div><span class="nette-dump-indent">   </span><span class="nette-dump-key">0</span> => <span class="nette-dump-number">1</span>
<span class="nette-dump-indent">   </span><span class="nette-dump-key">1</span> => <span class="nette-dump-string">"hello"</span> (5)
<span class="nette-dump-indent">   </span><span class="nette-dump-key">2</span> => <span class="nette-dump-array">array</span> (0)
<span class="nette-dump-indent">   </span><span class="nette-dump-key">3</span> => <span class="nette-toggle"><span class="nette-dump-array">array</span> (2)</span>
<div><span class="nette-dump-indent">   |  </span><span class="nette-dump-key">0</span> => <span class="nette-dump-number">1</span>
<span class="nette-dump-indent">   |  </span><span class="nette-dump-key">1</span> => <span class="nette-dump-number">2</span>
</div><span class="nette-dump-indent">   </span><span class="nette-dump-key">4</span> => <span class="nette-toggle-collapsed"><span class="nette-dump-array">array</span> (7)</span>
<div class="nette-collapsed"><span class="nette-dump-indent">   |  </span><span class="nette-dump-key">1</span> => <span class="nette-dump-number">1</span>
<span class="nette-dump-indent">   |  </span><span class="nette-dump-key">2</span> => <span class="nette-dump-number">2</span>
<span class="nette-dump-indent">   |  </span><span class="nette-dump-key">3</span> => <span class="nette-dump-number">3</span>
<span class="nette-dump-indent">   |  </span><span class="nette-dump-key">4</span> => <span class="nette-dump-number">4</span>
<span class="nette-dump-indent">   |  </span><span class="nette-dump-key">5</span> => <span class="nette-dump-number">5</span>
<span class="nette-dump-indent">   |  </span><span class="nette-dump-key">6</span> => <span class="nette-dump-number">6</span>
<span class="nette-dump-indent">   |  </span><span class="nette-dump-key">7</span> => <span class="nette-dump-number">7</span>
</div></div></pre>', Dump::toHtml(array(1, 'hello', array(), array(1, 2), array(1 => 1, 2, 3, 4, 5, 6, 7))) );

Assert::match( '<pre class="nette-dump"><span class="nette-toggle-collapsed"><span class="nette-dump-resource">stream resource</span></span>
<div class="nette-collapsed">%A%', Dump::toHtml(fopen(__FILE__, 'r')) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-object">stdClass</span> (0)
</pre>', Dump::toHtml((object) NULL) );

Assert::match( '<pre class="nette-dump"><span class="nette-toggle"><span class="nette-dump-object">Test</span> (3)</span>
<div><span class="nette-dump-indent">   </span><span class="nette-dump-key">x</span> => <span class="nette-toggle"><span class="nette-dump-array">array</span> (2)</span>
<div><span class="nette-dump-indent">   |  </span><span class="nette-dump-key">0</span> => <span class="nette-dump-number">10</span>
<span class="nette-dump-indent">   |  </span><span class="nette-dump-key">1</span> => <span class="nette-dump-null">NULL</span>
</div><span class="nette-dump-indent">   </span><span class="nette-dump-key">y</span> <span class="nette-dump-visibility">private</span> => <span class="nette-dump-string">"hello"</span> (5)
<span class="nette-dump-indent">   </span><span class="nette-dump-key">z</span> <span class="nette-dump-visibility">protected</span> => <span class="nette-dump-number">30.0</span>
</div></pre>', Dump::toHtml(new Test) );

Assert::match( '<pre class="nette-dump"><span class="nette-toggle-collapsed"><span class="nette-dump-object">Test</span> (3)</span>
<div class="nette-collapsed"><span class="nette-dump-indent">   </span><span class="nette-dump-key">x</span> => <span class="nette-toggle-collapsed"><span class="nette-dump-array">array</span> (2)</span>
<div class="nette-collapsed"><span class="nette-dump-indent">   |  </span><span class="nette-dump-key">0</span> => <span class="nette-dump-number">10</span>
<span class="nette-dump-indent">   |  </span><span class="nette-dump-key">1</span> => <span class="nette-dump-null">NULL</span>
</div><span class="nette-dump-indent">   </span><span class="nette-dump-key">y</span> <span class="nette-dump-visibility">private</span> => <span class="nette-dump-string">"hello"</span> (5)
<span class="nette-dump-indent">   </span><span class="nette-dump-key">z</span> <span class="nette-dump-visibility">protected</span> => <span class="nette-dump-number">30.0</span>
</div></pre>', Dump::toHtml(new Test, array(Dump::COLLAPSE => 1)) );
