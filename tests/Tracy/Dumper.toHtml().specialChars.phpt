<?php

/**
 * Test: Tracy\Dumper::toHtml() special chars
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


Assert::match(<<<XX
<pre class="tracy-dump"><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (9)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-key">0</span> => <span class="tracy-dump-string">''</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">1</span> => <span class="tracy-dump-string">' '</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">2</span> => <span class="tracy-dump-string">'utf \n\r\t string'</span> (14)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">3</span> => <span class="tracy-dump-string">'binary \\n\\r\\t string\\x00'</span> (18)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">4</span> => <span class="tracy-dump-string">'utf \\n\\r\\t\\xab string'</span> (21)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">5</span> => <span class="tracy-dump-string">'binary \\\\n\\\\r\\\\t\\\\xab string\\x00'</span> (25)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">6</span> => <span class="tracy-dump-string">'Iñtërnâtiônàlizætiøn'</span> (27)
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">7</span> => <span class="tracy-dump-string">'\\x00'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-key">8</span> => <span class="tracy-dump-string">'\\xff'</span>
</div></pre>
XX
, Dumper::toHtml([
	'',
	' ',
	"utf \n\r\t string",
	"binary \n\r\t string\x00",
	'utf \n\r\t\xab string',
	'binary \n\r\t\xab string' . "\x00",
	"I\xc3\xb1t\xc3\xabrn\xc3\xa2ti\xc3\xb4n\xc3\xa0liz\xc3\xa6ti\xc3\xb8n", // Iñtërnâtiônàlizætiøn,
	"\x00",
	"\xFF",
]));
