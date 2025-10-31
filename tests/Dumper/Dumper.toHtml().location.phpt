<?php

/**
 * Test: Tracy\Dumper::toHtml() with location
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


Assert::same("</pre>\n", substr(Dumper::toHtml(true, ['location' => true]), -7));


Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><a href="editor:%a%" class="tracy-dump-location" title="in file %a% on line %d%&#10;Click to open in editor">Dumper::toHtml([1], ['location' => true]) 📍</a
		><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (1)</span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-number">1</span>
		</div></pre>
		XX,
	Dumper::toHtml([1], ['location' => true]),
);


class Test
{
}

Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><a href="editor:%a%" class="tracy-dump-location" title="in file %a% on line %d%&#10;Click to open in editor">Dumper::toHtml(new Test, ['location' => true]) 📍</a
		><span class="tracy-dump-object" title="Declared in file %a% on line %d%&#10;Ctrl-Click to open in editor&#10;Alt-Click to expand/collapse all child nodes" data-tracy-href="editor:%a%">Test</span> <span class="tracy-dump-hash">#%d%</span></pre>
		XX,
	Dumper::toHtml(new Test, ['location' => true]),
);


Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"><span class="tracy-dump-object">Test</span> <span class="tracy-dump-hash">#%d%</span></pre>
		XX,
	Dumper::toHtml(new Test, ['location' => false]),
);


Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><a href="editor:%a%" class="tracy-dump-location" title="in file %a% on line %d%&#10;Click to open in editor">Dumper::toHtml(new Test, ['location' => Dumper::LOCATION_SOURCE]) 📍</a
		><span class="tracy-dump-object" title="Declared in file %a% on line %d%&#10;Ctrl-Click to open in editor&#10;Alt-Click to expand/collapse all child nodes" data-tracy-href="editor:%a%">Test</span> <span class="tracy-dump-hash">#%d%</span></pre>
		XX,
	Dumper::toHtml(new Test, ['location' => Dumper::LOCATION_SOURCE]),
);


Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-dump-object" title="Declared in file %a% on line %d%&#10;Ctrl-Click to open in editor&#10;Alt-Click to expand/collapse all child nodes" data-tracy-href="editor:%a%">Test</span> <span class="tracy-dump-hash">#%d%</span></pre>
		XX,
	Dumper::toHtml(new Test, ['location' => Dumper::LOCATION_CLASS]),
);
