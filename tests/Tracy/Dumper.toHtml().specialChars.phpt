<?php

/**
 * Test: Tracy\Dumper::toHtml() special chars
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


Assert::match(<<<'XX'
<pre class="tracy-dump tracy-light"
><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (13)</span>
<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-string">''</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-string">' '</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-string">'<span>\x00</span>'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-string">'<span>\xFF</span>'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">4</span> => <span class="tracy-dump-string" title="20 characters">'Iñtërnâtiônàlizætiøn'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">5</span> => <span class="tracy-dump-string" title="16 characters">
   'utf <span>\n</span>
    <span>\r\t</span>    <span>\e\x00</span> Iñtër<span>\n</span>'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">6</span> => <span class="tracy-dump-string" title="20 characters">'utf \n\r\t\xab Iñtër'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">7</span> => <span class="tracy-dump-string" title="22 bytes">
   'binary <span>\n</span>
    <span>\r\t</span>    <span>\e\x00</span> I<span>\xC3\xB1</span>t<span>\xC3\xAB</span>r <span>\xA0</span>'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">8</span> => <span class="tracy-dump-string" title="27 bytes">'binary \n\r\t\xab I<span>\xC3\xB1</span>t<span>\xC3\xAB</span>r <span>\xA0</span>'</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-string">'utf \n\r\t\xab Iñtër'</span> => <span class="tracy-dump-number">1</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-string">'utf <span>\n</span>
 <span>\r\t</span>    <span>\e\x00</span> Iñtër'</span> => <span class="tracy-dump-number">2</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-string">'utf <span>\n</span>
 <span>\r\t</span>    <span>\e\x00</span> I<span>\xC3\xB1</span>t<span>\xC3\xAB</span>r <span>\xA0</span>'</span> => <span class="tracy-dump-number">3</span>
<span class="tracy-dump-indent">   </span><span class="tracy-dump-string">'&lt;div> &amp;amp;'</span> => <span class="tracy-dump-string" title="11 characters">'&lt;div> &amp;amp;'</span>
</div></pre>
XX
, Dumper::toHtml([
	'',
	' ',
	"\x00",
	"\xFF",
	"I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n", // Iñtërnâtiônàlizætiøn,
	"utf \n\r\t\e\x00 Iñtër\n", // utf + control chars
	'utf \n\r\t\xab Iñtër', // slashes
	"binary \n\r\t\e\x00 Iñtër \xA0", // binary + control chars
	'binary \n\r\t\xab Iñtër ' . "\xA0", // binary + slashes
	'utf \n\r\t\xab Iñtër' => 1, // slashes in key
	"utf \n\r\t\e\x00 Iñtër" => 2, // utf + control chars in key
	"utf \n\r\t\e\x00 Iñtër \xA0" => 3, // binary + control chars in key,
	'<div> &amp;' => '<div> &amp;', // HTML
]));
