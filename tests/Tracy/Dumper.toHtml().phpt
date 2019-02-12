<?php

/**
 * Test: Tracy\Dumper::toHtml()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


Assert::match('<pre class="tracy-dump"><span class="tracy-dump-null">null</span>
</pre>', Dumper::toHtml(null));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-bool">true</span>
</pre>', Dumper::toHtml(true));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-bool">false</span>
</pre>', Dumper::toHtml(false));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0</span>
</pre>', Dumper::toHtml(0));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">1</span>
</pre>', Dumper::toHtml(1));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0.0</span>
</pre>', Dumper::toHtml(0.0));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">0.1</span>
</pre>', Dumper::toHtml(0.1));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">INF</span>
</pre>', Dumper::toHtml(INF));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">-INF</span>
</pre>', Dumper::toHtml(-INF));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">NAN</span>
</pre>', Dumper::toHtml(NAN));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-string">""</span>
</pre>', Dumper::toHtml(''));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-string">"0"</span>
</pre>', Dumper::toHtml('0'));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-string">"\\x00"</span>
</pre>', Dumper::toHtml("\x00"));

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (5)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">2</span> => <span class="tracy-dump-array">array</span> ()
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">3</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-number">2</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">4</span> => <span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-array">array</span> (7)</span>
<div class="tracy-collapsed" data-tracy-content="&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;1&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;1&lt;/span&gt;
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;2&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;2&lt;/span&gt;
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;3&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;3&lt;/span&gt;
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;4&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;4&lt;/span&gt;
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;5&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;5&lt;/span&gt;
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;6&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;6&lt;/span&gt;
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;7&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;7&lt;/span&gt;
"></div></div></pre>', Dumper::toHtml([1, 'hello', [], [1, 2], [1 => 1, 2, 3, 4, 5, 6, 7]]));

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-resource">stream resource</span> <span class="tracy-dump-hash">#%d%</span></span>
<div class="tracy-collapsed">%A%', Dumper::toHtml(fopen(__FILE__, 'r')));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-object">stdClass</span> <span class="tracy-dump-hash">#%a%</span>
</pre>', Dumper::toHtml(new stdClass));

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-toggle"><span class="tracy-dump-array">array</span> (2)</span>
<div><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">10</span>
<span class="tracy-dump-indent">   |  </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-null">null</span>
</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> <span class="tracy-dump-visibility">private</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> <span class="tracy-dump-visibility">protected</span> => <span class="tracy-dump-number">30.0</span>
</div></pre>', Dumper::toHtml(new Test));

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-array">array</span> (2)</span>
<div class="tracy-collapsed" data-tracy-content="&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;0&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;10&lt;/span&gt;
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;1&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-null&quot;&gt;null&lt;/span&gt;
"></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> <span class="tracy-dump-visibility">private</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> <span class="tracy-dump-visibility">protected</span> => <span class="tracy-dump-number">30.0</span>
</div></pre>', Dumper::toHtml(new Test, [Dumper::COLLAPSE_COUNT => 1]));

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">x</span> => <span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-array">array</span> (2)</span>
<div class="tracy-collapsed" data-tracy-content="&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;0&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;10&lt;/span&gt;
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;1&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-null&quot;&gt;null&lt;/span&gt;
"></div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">y</span> <span class="tracy-dump-visibility">private</span> => <span class="tracy-dump-string">"hello"</span> (5)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">z</span> <span class="tracy-dump-visibility">protected</span> => <span class="tracy-dump-number">30.0</span>
</div></pre>', Dumper::toHtml(new Test, [Dumper::COLLAPSE_COUNT => 1, Dumper::COLLAPSE => false]));

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div class="tracy-collapsed" data-tracy-content="&lt;span class=&quot;tracy-dump-indent&quot;&gt;   &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;x&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-toggle&quot;&gt;&lt;span class=&quot;tracy-dump-array&quot;&gt;array&lt;/span&gt; (2)&lt;/span&gt;
&lt;div&gt;&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;0&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;10&lt;/span&gt;
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;1&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-null&quot;&gt;null&lt;/span&gt;
&lt;/div&gt;&lt;span class=&quot;tracy-dump-indent&quot;&gt;   &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;y&lt;/span&gt; &lt;span class=&quot;tracy-dump-visibility&quot;&gt;private&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-string&quot;&gt;&quot;hello&quot;&lt;/span&gt; (5)
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;z&lt;/span&gt; &lt;span class=&quot;tracy-dump-visibility&quot;&gt;protected&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;30.0&lt;/span&gt;
"></div></pre>', Dumper::toHtml(new Test, [Dumper::COLLAPSE => true]));

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle tracy-collapsed"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%a%</span></span>
<div class="tracy-collapsed" data-tracy-content="&lt;span class=&quot;tracy-dump-indent&quot;&gt;   &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;x&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-toggle&quot;&gt;&lt;span class=&quot;tracy-dump-array&quot;&gt;array&lt;/span&gt; (2)&lt;/span&gt;
&lt;div&gt;&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;0&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;10&lt;/span&gt;
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   |  &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;1&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-null&quot;&gt;null&lt;/span&gt;
&lt;/div&gt;&lt;span class=&quot;tracy-dump-indent&quot;&gt;   &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;y&lt;/span&gt; &lt;span class=&quot;tracy-dump-visibility&quot;&gt;private&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-string&quot;&gt;&quot;hello&quot;&lt;/span&gt; (5)
&lt;span class=&quot;tracy-dump-indent&quot;&gt;   &lt;/span&gt;&lt;span class=&quot;tracy-dump-key&quot;&gt;z&lt;/span&gt; &lt;span class=&quot;tracy-dump-visibility&quot;&gt;protected&lt;/span&gt; =&gt; &lt;span class=&quot;tracy-dump-number&quot;&gt;30.0&lt;/span&gt;
"></div></pre>', Dumper::toHtml(new Test, [Dumper::COLLAPSE => 3]));

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">Closure</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">file</span> => <span class="tracy-dump-string">"%a%"</span> (%i%)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">line</span> => <span class="tracy-dump-number">%i%</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">variables</span> => <span class="tracy-dump-array">array</span> ()
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">parameters</span> => <span class="tracy-dump-string">""</span>
</div></pre>', Dumper::toHtml(function () {}));

Assert::match('<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-object">SplFileInfo</span> <span class="tracy-dump-hash">#%a%</span></span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">path</span> => <span class="tracy-dump-string">"%a%"</span> (%d%)
</div></pre>', Dumper::toHtml(new SplFileInfo(__FILE__)));

Assert::match('<pre class="tracy-dump"><span class="tracy-dump-object">class@anonymous</span> <span class="tracy-dump-hash">#%a%</span>
</pre>', Dumper::toHtml(new class {
}));
