<?php

/**
 * Test: Tracy\Dumper::toHtml()
 */

use Tracy\Dumper,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Test
{
	public $x = array(10, NULL);

	private $y = 'hello';

	protected $z = 30.0;
}


Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-null">NULL</span>
</pre>', Dumper::toHtml(NULL) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-bool">TRUE</span>
</pre>', Dumper::toHtml(TRUE) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-bool">FALSE</span>
</pre>', Dumper::toHtml(FALSE) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-number">0</span>
</pre>', Dumper::toHtml(0) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-number">1</span>
</pre>', Dumper::toHtml(1) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-number">0.0</span>
</pre>', Dumper::toHtml(0.0) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-number">0.1</span>
</pre>', Dumper::toHtml(0.1) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-number">INF</span>
</pre>', Dumper::toHtml(INF) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-number">-INF</span>
</pre>', Dumper::toHtml(-INF) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-number">NAN</span>
</pre>', Dumper::toHtml(NAN) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-string">""</span>
</pre>', Dumper::toHtml('') );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-string">"0"</span>
</pre>', Dumper::toHtml('0') );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-string">"\\x00"</span>
</pre>', Dumper::toHtml("\x00") );

Assert::match( '<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (5)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">2</span> => <span class="tracy-dump-array">array</span> ()
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">3</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-number">2</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">4</span> => <span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-array">array</span> (7)</span>
<div class="tracy-collapsed"><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">2</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">3</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">4</span> => <span class="tracy-dump-number">4</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">5</span> => <span class="tracy-dump-number">5</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">6</span> => <span class="tracy-dump-number">6</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">7</span> => <span class="tracy-dump-number">7</span>
</div></div></pre>', Dumper::toHtml(array(1, 'hello', array(), array(1, 2), array(1 => 1, 2, 3, 4, 5, 6, 7))) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">#%d%</span></span>
<div class="tracy-collapsed">%A%', Dumper::toHtml(fopen(__FILE__, 'r')) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%a%</span>
</pre>', Dumper::toHtml(new stdClass) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">10</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-null">NULL</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> <span class="tracy-dump-visibility">private</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> <span class="tracy-dump-visibility">protected</span> => <span class="tracy-dump-number">30.0</span>
</div></pre>', Dumper::toHtml(new Test) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-array">array</span> (2)</span>
<div class="tracy-collapsed"><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">10</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-null">NULL</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> <span class="tracy-dump-visibility">private</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> <span class="tracy-dump-visibility">protected</span> => <span class="tracy-dump-number">30.0</span>
</div></pre>', Dumper::toHtml(new Test, array(Dumper::COLLAPSE_COUNT => 1)) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-array">array</span> (2)</span>
<div class="tracy-collapsed"><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">10</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-null">NULL</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> <span class="tracy-dump-visibility">private</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> <span class="tracy-dump-visibility">protected</span> => <span class="tracy-dump-number">30.0</span>
</div></pre>', Dumper::toHtml(new Test, array(Dumper::COLLAPSE_COUNT => 1, Dumper::COLLAPSE => FALSE)) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div class="tracy-collapsed"><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">10</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-null">NULL</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> <span class="tracy-dump-visibility">private</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> <span class="tracy-dump-visibility">protected</span> => <span class="tracy-dump-number">30.0</span>
</div></pre>', Dumper::toHtml(new Test, array(Dumper::COLLAPSE => TRUE)) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div class="tracy-collapsed"><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">10</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-null">NULL</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> <span class="tracy-dump-visibility">private</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> <span class="tracy-dump-visibility">protected</span> => <span class="tracy-dump-number">30.0</span>
</div></pre>', Dumper::toHtml(new Test, array(Dumper::COLLAPSE => 3)) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Closure</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">file</span> => <span class="tracy-dump-string">"%a%"</span> (%i%)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">line</span> => <span class="tracy-dump-number">%i%</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">variables</span> => <span class="tracy-dump-array">array</span> ()
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">parameters</span> => <span class="tracy-dump-string">""</span>
</div></pre>', Dumper::toHtml(function () {}));

Assert::match( '<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">SplFileInfo</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">path</span> => <span class="tracy-dump-string">"%a%"</span> (%d%)
</div></pre>', Dumper::toHtml(new SplFileInfo(__FILE__)) );
