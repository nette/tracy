<?php declare(strict_types=1);

/**
 * Test: Tracy\CodeHighlighter::hideSensitive()
 */

use Tester\Assert;
use Tracy\CodeHighlighter;

require __DIR__ . '/../bootstrap.php';


test('sensitive region is replaced', function () {
	Assert::same(
		'$a = *****; $b = *****;',
		CodeHighlighter::hideSensitive('$a = /*sensitive{*/\'secret\'/*}*/; $b = /*sensitive{*/1/*}*/;'),
	);
});


test('multiline region keeps line numbering', function () {
	Assert::same(
		"\$a = *****\n\n;\n\$b = 1;",
		CodeHighlighter::hideSensitive("\$a = /*sensitive{*/[\n'secret',\n]/*}*/;\n\$b = 1;"),
	);
});


test('highlighted line is not shifted by multiline region', function () {
	$code = "<?php\n\$a = /*sensitive{*/[\n'secret',\n]/*}*/;\nthrow \$e;\n";
	$html = CodeHighlighter::highlightPhp($code, 5);
	Assert::notContains('secret', $html);
	Assert::match("%A%<span class='tracy-line-highlight'>5:    throw \$e;%A%", $html);
});
