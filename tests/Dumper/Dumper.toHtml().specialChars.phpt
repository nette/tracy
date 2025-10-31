<?php

/**
 * Test: Tracy\Dumper::toHtml() special chars
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle"><span class="tracy-dump-array">array</span> (14)</span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">0</span> => <span class="tracy-dump-string"><span>'</span><span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">1</span> => <span class="tracy-dump-string"><span>'</span> <span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">2</span> => <span class="tracy-dump-string"><span>'</span><i>\x00</i><span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">3</span> => <span class="tracy-dump-string"><span>'</span><i>\xFF</i><span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">4</span> => <span class="tracy-dump-string" title="20 characters"><span>'</span>Iñtërnâtiônàlizætiøn<span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">5</span> => <span class="tracy-toggle">string</span>
		<div class="tracy-dump-string" title="16 characters"><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-lq">'</span>utf <i>\n</i>
		<span class="tracy-dump-indent">   |   </span><i>\r\t</i>    <i>\e\x00</i> Iñtër<i>\n</i><span>'</span>
		</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">6</span> => <span class="tracy-dump-string" title="20 characters"><span>'</span>utf \n\r\t\xab Iñtër<span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">7</span> => <span class="tracy-toggle">string</span>
		<div class="tracy-dump-string" title="22 bytes"><span class="tracy-dump-indent">   |  </span><span class="tracy-dump-lq">'</span>binary <i>\n</i>
		<span class="tracy-dump-indent">   |   </span><i>\r\t</i>    <i>\e\x00</i> I<i>\xC3\xB1</i>t<i>\xC3\xAB</i>r <i>\xA0</i><span>'</span>
		</div><span class="tracy-dump-indent">   </span><span class="tracy-dump-number">8</span> => <span class="tracy-dump-string" title="27 bytes"><span>'</span>binary \n\r\t\xab I<i>\xC3\xB1</i>t<i>\xC3\xAB</i>r <i>\xA0</i><span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>utf \n\r\t\xab Iñtër<span>'</span></span> => <span class="tracy-dump-number">1</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>utf <i>\n</i>
		<span class="tracy-dump-indent">    </span><i>\r\t</i>    <i>\e\x00</i> Iñtër<span>'</span></span> => <span class="tracy-dump-number">2</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>utf <i>\n</i>
		<span class="tracy-dump-indent">    </span><i>\r\t</i>    <i>\e\x00</i> I<i>\xC3\xB1</i>t<i>\xC3\xAB</i>r <i>\xA0</i><span>'</span></span> => <span class="tracy-dump-number">3</span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-string"><span class='tracy-dump-lq'>'</span>&lt;div> &amp;amp;<span>'</span></span> => <span class="tracy-dump-string" title="11 characters"><span>'</span>&lt;div> &amp;amp;<span>'</span></span>
		<span class="tracy-dump-indent">   </span><span class="tracy-dump-number">9</span> => <span class="tracy-dump-string"><span>'</span><i>\u{FEFF}</i><span>'</span></span>
		</div></pre>
		XX,
	Dumper::toHtml([
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
		"\xEF\xBB\xBF", // BOM
	], [Dumper::COLLAPSE => false]),
);
