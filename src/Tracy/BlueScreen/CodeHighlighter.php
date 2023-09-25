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
		if (function_exists('ini_set')) {
			ini_set('highlight.comment', '#998; font-style: italic');
			ini_set('highlight.default', '#000');
			ini_set('highlight.html', '#06B');
			ini_set('highlight.keyword', '#D24; font-weight: bold');
			ini_set('highlight.string', '#080');
		}

		$source = preg_replace('#(__halt_compiler\s*\(\)\s*;).*#is', '$1', $code);
		$source = str_replace(["\r\n", "\r"], "\n", $source);
		$source = preg_replace('#/\*sensitive\{\*/.*?/\*\}\*/#s', Dumper\Describer::HiddenValue, $source);
		$source = explode("\n", highlight_string($source, true));
		$out = $source[0]; // <code><span color=highlight.html>
		$tmp = str_replace('<br />', "\n", $source[1]);
		$out .= self::highlightLine($tmp, $line, $column);
		$out = str_replace('&nbsp;', ' ', $out) . $source[2] . @$source[3];
		return "<pre class='tracy-code'><div>$out</div></pre>";
	}


	/**
	 * Returns syntax highlighted source code to Terminal.
	 */
	public static function highlightPhpCli(string $code, int $line, int $column = 0): string
	{
		return Helpers::htmlToAnsi(
			self::highlightPhp($code, $line, $column),
			[
				'color: ' . ini_get('highlight.comment') => '1;30',
				'color: ' . ini_get('highlight.default') => '1;36',
				'color: ' . ini_get('highlight.html') => '1;35',
				'color: ' . ini_get('highlight.keyword') => '1;37',
				'color: ' . ini_get('highlight.string') => '1;32',
				'tracy-line' => '1;30',
				'tracy-line-highlight' => "1;37m\e[41",
			],
		);
	}
}
