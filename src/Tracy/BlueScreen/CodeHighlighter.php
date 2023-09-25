<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


/** @internal */
final class CodeHighlighter
{
	private const DisplayLines = 15;


	/**
	 * Extract a snippet from the code, highlights the row and column, and adds line numbers.
	 */
	public static function highlightLine(string $html, int $line, int $column = 0): string
	{
		$html = str_replace("\r\n", "\n", $html);
		$lines = explode("\n", "\n" . $html);
		$startLine = max(1, min($line, count($lines) - 1) - (int) floor(self::DisplayLines * 2 / 3));
		$endLine = min($startLine + self::DisplayLines - 1, count($lines) - 1);
		$numWidth = strlen((string) $endLine);
		$openTags = $closeTags = [];
		$out = '';

		for ($n = 1; $n <= $endLine; $n++) {
			if ($n === $startLine) {
				$out = implode('', $openTags);
			}
			if ($n === $line) {
				$out .= implode('', $closeTags);
			}

			preg_replace_callback('#</?(\w+)[^>]*>#', function ($m) use (&$openTags, &$closeTags) {
				if ($m[0][1] === '/') {
					array_pop($openTags);
					array_shift($closeTags);
				} else {
					$openTags[] = $m[0];
					array_unshift($closeTags, "</$m[1]>");
				}
			}, $lines[$n]);

			if ($n === $line) {
				$s = strip_tags($lines[$n]);
				if ($column) {
					$s = preg_replace(
						'#((?:&.*?;|[^&]){' . ($column - 1) . '})(&.*?;|.)#u',
						'\1<span class="tracy-column-highlight">\2</span>',
						$s . ' ',
						1,
					);
				}
				$out .= sprintf("<span class='tracy-line-highlight'>%{$numWidth}s:    %s</span>\n%s", $n, $s, implode('', $openTags));
			} else {
				$out .= sprintf("<span class='tracy-line'>%{$numWidth}s:</span>    %s\n", $n, $lines[$n]);
			}
		}

		$out .= implode('', $closeTags);
		return $out;
	}


	/**
	 * Returns syntax highlighted source code.
	 */
	public static function highlightPhp(string $code, int $line, int $column = 0): string
	{
		$html = self::highlightPhpCode($code);
		$html = self::highlightLine($html, $line, $column);
		return "<pre class='tracy-code'><div><code>$html</code></div></pre>";
	}


	private static function highlightPhpCode(string $code): string
	{
		$code = str_replace("\r\n", "\n", $code);
		$code = preg_replace('#(__halt_compiler\s*\(\)\s*;).*#is', '$1', $code);
		$code = rtrim($code);
		$code = preg_replace('#/\*sensitive\{\*/.*?/\*\}\*/#s', Dumper\Describer::HiddenValue, $code);

		$last = $out = '';
		foreach (\PhpToken::tokenize($code) as $token) {
			$next = match ($token->id) {
				T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML => 'tracy-code-comment',
				T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG, T_LINE, T_FILE, T_DIR, T_TRAIT_C, T_METHOD_C, T_FUNC_C, T_NS_C, T_CLASS_C,
				T_STRING, T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_NAME_RELATIVE => '',
				T_LNUMBER, T_DNUMBER => 'tracy-dump-number',
				T_VARIABLE => 'tracy-code-var',
				T_ENCAPSED_AND_WHITESPACE, T_CONSTANT_ENCAPSED_STRING => 'tracy-dump-string',
				T_WHITESPACE => $last,
				default => 'tracy-code-keyword',
			};

			if ($last !== $next) {
				if ($last !== '') {
					$out .= '</span>';
				}
				$last = $next;
				if ($last !== '') {
					$out .= "<span class='$last'>";
				}
			}

			$out .= strtr($token->text, ['<' => '&lt;', '>' => '&gt;', '&' => '&amp;', "\t" => '    ']);
		}
		if ($last !== '') {
			$out .= '</span>';
		}
		return $out;
	}


	/**
	 * Returns syntax highlighted source code to Terminal.
	 */
	public static function highlightPhpCli(string $code, int $line, int $column = 0): string
	{
		return Helpers::htmlToAnsi(
			self::highlightPhp($code, $line, $column),
			[
				'string' => '1;32',
				'number' => '1;32',
				'code-comment' => '1;30',
				'code-keyword' => '1;37',
				'code-var' => '1;36',
				'line' => '1;30',
				'line-highlight' => "1;37m\e[41",
			],
		);
	}
}
