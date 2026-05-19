<?php declare(strict_types=1);

/**
 * Tests for Tracy Latte-based template compiler.
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$engine = new Tracy\Tools\Compiler\Engine;


function compile(string $template): string
{
	global $engine;
	static $n = 0;
	$name = 'test' . $n++;
	$engine->setLoader(new Latte\Loaders\StringLoader([$name => $template]));
	$engine->setContentType(Latte\ContentType::Html);
	return $engine->compile($name);
}


function compileText(string $template): string
{
	global $engine;
	static $n = 0;
	$name = 'text' . $n++;
	$engine->setLoader(new Latte\Loaders\StringLoader([$name => $template]));
	$engine->setContentType(Latte\ContentType::Text);
	return $engine->compile($name);
}


// Escaping

test('HTML text is escaped', function () {
	$r = compile('{$foo}');
	Assert::contains('Tracy\Helpers::escapeHtml($foo)', $r);
});

test('|noescape disables escaping', function () {
	$r = compile('{$foo|noescape}');
	Assert::notContains('escapeHtml', $r);
	Assert::contains('$foo', $r);
});

test('script context uses jsonEncode', function () {
	$r = compile('<script>{$data}</script>');
	Assert::contains('Tracy\Helpers::jsonEncode($data, true)', $r);
});

test('text mode disables escaping', function () {
	$r = compileText('{$foo}');
	Assert::notContains('escapeHtml', $r);
	Assert::notContains('jsonEncode', $r);
});

test('regular expression is escaped', function () {
	$r = compile('{$name}');
	Assert::contains('Tracy\Helpers::escapeHtml($name)', $r);
});


// noEscape for known functions

test('editorLink not escaped', function () {
	$r = compile('{Helpers::editorLink($f, $l)}');
	Assert::notContains('escapeHtml', $r);
});

test('highlightFile not escaped', function () {
	$r = compile('{BlueScreen::highlightFile($f, $l)}');
	Assert::notContains('escapeHtml', $r);
});

test('$dump() not escaped', function () {
	$r = compile('{$dump($v)}');
	Assert::notContains('escapeHtml', $r);
});

test('Dumper::toHtml not escaped', function () {
	$r = compile('{Dumper::toHtml($v)}');
	Assert::notContains('escapeHtml', $r);
});

test('formatMessage not escaped', function () {
	$r = compile('{$blueScreen->formatMessage($ex)}');
	Assert::notContains('escapeHtml', $r);
});


// |json filter

test('|json in text context', function () {
	$r = compile('<span>{=[1,2,3]|json}</span>');
	Assert::contains('Tracy\Helpers::jsonEncode([1, 2, 3])', $r);
	Assert::notContains('escapeHtml', $r);
});

test('|json in attribute uses single quotes', function () {
	$r = compile('<span foo={=[1,2,3]|json}></span>');
	Assert::contains('jsonEncode', $r);
	Assert::contains("foo=\\'", $r);
});

test('|json on data attribute', function () {
	$r = compile('<pre data-snapshot={$snapshot|json}></pre>');
	Assert::contains('Tracy\Helpers::jsonEncode($snapshot)', $r);
	Assert::contains("data-snapshot=\\'", $r);
	Assert::notContains('escapeHtml', $r);
});


// Attribute expressions

test('regular attr={$var}', function () {
	$r = compile('<div attr={$foo}>');
	Assert::contains('escapeHtml', $r);
	Assert::contains('attr="', $r);
});

test('boolean attr (hidden)', function () {
	$r = compile('<div hidden={$hidden}></div>');
	Assert::contains("' hidden'", $r);
});

test('class={[...]} list attribute', function () {
	$r = compile('<div class={[foo, $cond ? bar]}>');
	Assert::contains('array_filter', $r);
	Assert::contains('implode', $r);
});

test('noEscape in attribute (formatSnapshotAttribute)', function () {
	$r = compile('<meta content={Dumper::formatSnapshotAttribute($s)}>');
	Assert::notContains('escapeHtml', $r);
});


// Tags

test('{varType}', function () {
	$r = compile('{varType string $name}');
	Assert::contains('/** @var string $name */', $r);
});

test('{use}', function () {
	$r = compile('{use Tracy\Helpers}');
	Assert::contains('use Tracy\Helpers;', $r);
});

test('{var}', function () {
	$r = compile('{var $x = 42}');
	Assert::contains('$x = 42', $r);
});

test('{do}', function () {
	$r = compile('{do $x = 1}');
	Assert::contains('$x = 1', $r);
});

test('{if}/{else}/{/if}', function () {
	$r = compile('{if $cond}A{else}B{/if}');
	Assert::contains('if ($cond)', $r);
	Assert::contains('else', $r);
});

test('{foreach}', function () {
	$r = compile('{foreach $items as $item}{$item}{/foreach}');
	Assert::contains('foreach ($items as $item)', $r);
});

test('{exitIf}', function () {
	$r = compile('{exitIf $done}');
	Assert::contains('if ($done)', $r);
	Assert::contains('return;', $r);
});

test('{continueIf}', function () {
	$r = compile('{foreach $a as $b}{continueIf $b}{/foreach}');
	Assert::contains('continue;', $r);
});

test('{define}/{include} block', function () {
	$r = compile('{define foo}hello{/define}{include foo}');
	Assert::contains("\$_blocks['foo'] = function", $r);
	Assert::contains("\$_blocks['foo']()", $r);
});

test('{include file}', function () {
	$r = compile("{include 'section.phtml'}");
	Assert::contains("require __DIR__ . '/section.phtml'", $r);
});

test('{try}/{rollback}', function () {
	$r = compile('{try}{do $x = dangerous()}{rollback}{do $x = fallback()}{/try}');
	Assert::contains('try {', $r);
	Assert::contains('catch (\Throwable)', $r);
});


// Output format

test('starts with <?php declare', function () {
	$r = compile('{$x}');
	Assert::true(str_starts_with($r, '<?php declare(strict_types=1);'));
});
