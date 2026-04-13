<?php declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;


require __DIR__ . '/../bootstrap.php';


// inline-special characters: escaped anywhere
Assert::same('\\\\', Helpers::escapeMd('\\'));
Assert::same('\`', Helpers::escapeMd('`'));
Assert::same('\*', Helpers::escapeMd('*'));
Assert::same('\[', Helpers::escapeMd('['));
Assert::same('\]', Helpers::escapeMd(']'));
Assert::same('\<', Helpers::escapeMd('<'));
Assert::same('\|', Helpers::escapeMd('|'));
Assert::same('\~', Helpers::escapeMd('~'));

// underscore: escaped only at word boundary (CommonMark intra-word _ is not emphasis)
Assert::same('\_', Helpers::escapeMd('_'));
Assert::same('\_foo\_', Helpers::escapeMd('_foo_'));
Assert::same('\_italic\_', Helpers::escapeMd('_italic_'));
Assert::same('foo_bar', Helpers::escapeMd('foo_bar'));            // intra-word: clean
Assert::same('PHP_VERSION', Helpers::escapeMd('PHP_VERSION'));    // intra-word: clean
Assert::same('foo_bar.php', Helpers::escapeMd('foo_bar.php'));    // intra-word in path: clean
Assert::same('some_module/sub_dir.php', Helpers::escapeMd('some_module/sub_dir.php'));
Assert::same('\_\_foo\_\_', Helpers::escapeMd('__foo__'));    // Python dunder: all escaped
Assert::same('\_\_init\_\_', Helpers::escapeMd('__init__'));
Assert::same('\_\_', Helpers::escapeMd('__'));
Assert::same('\_\_\_', Helpers::escapeMd('___'));
Assert::same('1_2', Helpers::escapeMd('1_2'));                    // digits around _: intra-word

// block-start characters: # + need following whitespace; > always; - = need whitespace, repeat, or EOL
Assert::same('\#', Helpers::escapeMd('#'));
Assert::same('\>', Helpers::escapeMd('>'));
Assert::same('\-', Helpers::escapeMd('-'));
Assert::same('\+', Helpers::escapeMd('+'));
Assert::same('\=', Helpers::escapeMd('='));

// safe characters: never escaped
Assert::same('(', Helpers::escapeMd('('));
Assert::same(')', Helpers::escapeMd(')'));
Assert::same('{', Helpers::escapeMd('{'));
Assert::same('}', Helpers::escapeMd('}'));
Assert::same('.', Helpers::escapeMd('.'));
Assert::same('!', Helpers::escapeMd('!'));

// plain text passes through untouched
Assert::same('', Helpers::escapeMd(''));
Assert::same('hello world', Helpers::escapeMd('hello world'));
Assert::same('123 abc', Helpers::escapeMd('123 abc'));

// file paths and dotted identifiers
Assert::same('agent.latte', Helpers::escapeMd('agent.latte'));
Assert::same('1.5', Helpers::escapeMd('1.5'));
Assert::same('PHP 8.5.4', Helpers::escapeMd('PHP 8.5.4'));

// function calls and bracket pairs
Assert::same('foo()', Helpers::escapeMd('foo()'));
Assert::same('Array\[0\]', Helpers::escapeMd('Array[0]'));
Assert::same('{key: value}', Helpers::escapeMd('{key: value}'));

// emphasis-like patterns
Assert::same('\*emphasis\*', Helpers::escapeMd('*emphasis*'));
Assert::same('\*\*bold\*\*', Helpers::escapeMd('**bold**'));

// HTML / autolink: only `<` needs escaping inline
Assert::same('\<script>alert(1)\</script>', Helpers::escapeMd('<script>alert(1)</script>'));

// block-start chars in middle of line: untouched
Assert::same('a + b', Helpers::escapeMd('a + b'));
Assert::same('a-b', Helpers::escapeMd('a-b'));
Assert::same('text - middle - text', Helpers::escapeMd('text - middle - text'));
Assert::same('issue #1234', Helpers::escapeMd('issue #1234'));
Assert::same('a = b', Helpers::escapeMd('a = b'));

// block-start chars at start of line, but not actual block markers in CommonMark: untouched
Assert::same('#1234 issue', Helpers::escapeMd('#1234 issue'));    // `#` without space: not heading
Assert::same('-text', Helpers::escapeMd('-text'));                // `-` without space: not bullet
Assert::same('+text', Helpers::escapeMd('+text'));                // `+` without space: not bullet
Assert::same('=text', Helpers::escapeMd('=text'));                // `=` without =/space: not setext

// block-start chars at start of line that ARE block markers: escaped
Assert::same('\# heading', Helpers::escapeMd('# heading'));
Assert::same('\## heading', Helpers::escapeMd('## heading'));    // multi-hash
Assert::same('\### heading', Helpers::escapeMd('### heading'));
Assert::same('\> quote', Helpers::escapeMd('> quote'));
Assert::same('\>x', Helpers::escapeMd('>x'));                    // CommonMark blockquote: no space needed
Assert::same('\- bullet', Helpers::escapeMd('- bullet'));
Assert::same('\+ plus', Helpers::escapeMd('+ plus'));

// setext heading underline: line of `=` or `-` chars
Assert::same("title\n\\===", Helpers::escapeMd("title\n==="));
Assert::same("title\n\\---", Helpers::escapeMd("title\n---"));

// horizontal rule: line of 3+ `-`, `*`, or `_` chars
Assert::same('\---', Helpers::escapeMd('---'));
Assert::same('\*\*\*', Helpers::escapeMd('***'));
Assert::same('\_\_\_', Helpers::escapeMd('___'));

// ordered list markers (1-9 digits + . or `)` followed by whitespace or end)
Assert::same('1\. item', Helpers::escapeMd('1. item'));
Assert::same('12\. item', Helpers::escapeMd('12. item'));
Assert::same('5\) item', Helpers::escapeMd('5) item'));
Assert::same('1\.', Helpers::escapeMd('1.'));
Assert::same("1\\.\nrest", Helpers::escapeMd("1.\nrest"));

// digits + dot/paren without whitespace: not a list marker, no escape
Assert::same('1.5', Helpers::escapeMd('1.5'));
Assert::same('foo 1. bar', Helpers::escapeMd('foo 1. bar'));
Assert::same('1.2.3', Helpers::escapeMd('1.2.3'));
Assert::same('1234567890. text', Helpers::escapeMd('1234567890. text'));    // 10 digits: not a list marker

// any leading whitespace cancels line-start semantics (input treated as inlined fragment)
Assert::same(' # heading', Helpers::escapeMd(' # heading'));
Assert::same('  # heading', Helpers::escapeMd('  # heading'));
Assert::same('   # heading', Helpers::escapeMd('   # heading'));
Assert::same('  - bullet', Helpers::escapeMd('  - bullet'));
Assert::same('  1. item', Helpers::escapeMd('  1. item'));
Assert::same(' #123', Helpers::escapeMd(' #123'));
Assert::same("\t# not heading", Helpers::escapeMd("\t# not heading"));    // tab-indented

// multi-line: line-start escape applies after each \n
Assert::same("line1\n\\# heading\nline3", Helpers::escapeMd("line1\n# heading\nline3"));
Assert::same("line1\n\\- bullet\nline3", Helpers::escapeMd("line1\n- bullet\nline3"));
Assert::same("line1\n\\> quote", Helpers::escapeMd("line1\n> quote"));
Assert::same("line1\n1\\. item", Helpers::escapeMd("line1\n1. item"));
Assert::same("line1\n\\## h2", Helpers::escapeMd("line1\n## h2"));

// CRLF (Windows) line endings
Assert::same("line1\r\n\\# heading", Helpers::escapeMd("line1\r\n# heading"));
Assert::same("line1\r\n1\\. item", Helpers::escapeMd("line1\r\n1. item"));

// CR-only (classic Mac) line endings
Assert::same("line1\r\\# heading", Helpers::escapeMd("line1\r# heading"));
Assert::same("line1\r\\- bullet", Helpers::escapeMd("line1\r- bullet"));

// empty multi-line
Assert::same("\n", Helpers::escapeMd("\n"));
Assert::same("\r\n", Helpers::escapeMd("\r\n"));

// already-escaped input: backslash is escaped, second char stays unaffected (no longer at line start)
Assert::same('\\\\\*', Helpers::escapeMd('\*'));        // user typed "\*" → "\\\*"
Assert::same('\\\#', Helpers::escapeMd('\#'));          // # is preceded by \, not at line start
Assert::same('\\\\\\\\', Helpers::escapeMd('\\\\'));

// idempotency: escapeMd(escapeMd($s)) over-escapes (single-call contract)
Assert::same('\\\\\*', Helpers::escapeMd(Helpers::escapeMd('*')));    // backslash escaped, then * escaped again
Assert::same('\\\#', Helpers::escapeMd(Helpers::escapeMd('#')));      // backslash escaped, # no longer at line start

// fenced code block: backticks neutralized via inline escape
Assert::same('\`\`\`php', Helpers::escapeMd('```php'));

// table separators and strikethrough (GFM)
Assert::same('a\|b\|c', Helpers::escapeMd('a|b|c'));
Assert::same('\~strike\~', Helpers::escapeMd('~strike~'));

// link/image syntax: brackets escaped, parens stay
Assert::same('\[link\](url)', Helpers::escapeMd('[link](url)'));
Assert::same('!\[alt\](img.png)', Helpers::escapeMd('![alt](img.png)'));

// backslashes in paths (Windows-style)
Assert::same('path\\\to\\\file', Helpers::escapeMd('path\to\file'));

// realistic exception messages
Assert::same(
	'Cannot call method foo_bar() on \<null> in /app/some_module/file.php',
	Helpers::escapeMd('Cannot call method foo_bar() on <null> in /app/some_module/file.php'),
);
Assert::same(
	'PDOException: SELECT \* FROM \`users\` WHERE id\|name = ?',
	Helpers::escapeMd('PDOException: SELECT * FROM `users` WHERE id|name = ?'),
);

// HTML entities pass through untouched
Assert::same('&amp; &lt; &#39;', Helpers::escapeMd('&amp; &lt; &#39;'));

// multi-byte UTF-8 passes through
Assert::same('česky → ×3', Helpers::escapeMd('česky → ×3'));

// mixed input type coercion
Assert::same('123', Helpers::escapeMd(123));
Assert::same('1.5', Helpers::escapeMd(1.5));
Assert::same('', Helpers::escapeMd(null));
Assert::same('1', Helpers::escapeMd(true));
