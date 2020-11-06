<?php

/**
 * Test: Tracy\Dumper::toHtml() with location
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


Assert::same("</pre>\n", substr(Dumper::toHtml(true, ['location' => true]), -7));


Assert::match(<<<'XX'
<pre class="tracy-dump" title="Dumper::toHtml([1], [&apos;location&apos; =&gt; true]))
in file %a% on line %d%" data-tracy-href="editor:%a%"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-number">1</span>
</div><small>in <a href="editor:%a%">%a%:%d%</a></small></pre>
XX
, Dumper::toHtml([1], ['location' => true]));


class Test
{
}

Assert::match(<<<'XX'
<pre class="tracy-dump" title="Dumper::toHtml(new Test, [&apos;location&apos; =&gt; true]))
in file %a% on line %d%" data-tracy-href="editor:%a%"><span class="tracy-dump-object" title="Declared in file %a% on line %d%" data-tracy-href="editor:%a%">Test</span> <span class="tracy-dump-hash">#%d%</span>
<small>in <a href="editor:%a%">%a%:%d%</a></small></pre>
XX
, Dumper::toHtml(new Test, ['location' => true]));


Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></pre>
XX
, Dumper::toHtml(new Test, ['location' => false]));


Assert::match(<<<'XX'
<pre class="tracy-dump" title="Dumper::toHtml(new Test, [&apos;location&apos; =&gt; Dumper::LOCATION_SOURCE]))
in file %a% on line %d%" data-tracy-href="editor:%a%"><span class="tracy-dump-object" title="Declared in file %a% on line %d%" data-tracy-href="editor:%a%">Test</span> <span class="tracy-dump-hash">#%d%</span>
<small>in <a href="editor:%a%">%a%:%d%</a></small></pre>
XX
, Dumper::toHtml(new Test, ['location' => Dumper::LOCATION_SOURCE]));


Assert::match(<<<'XX'
<pre class="tracy-dump"><span class="tracy-dump-object" title="Declared in file %a% on line %d%" data-tracy-href="editor:%a%">Test</span> <span class="tracy-dump-hash">#%d%</span></pre>
XX
, Dumper::toHtml(new Test, ['location' => Dumper::LOCATION_CLASS]));
