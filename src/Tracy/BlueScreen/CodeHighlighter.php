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
		$source = explode("\n", "\n" . str_replace("\r\n", "\n", $html));
		$out = '';
		$spans = 1;
		$start = $i = max(1, min($line, count($source) - 1) - (int) floor(self::DisplayLines * 2 / 3));
		while (--$i >= 1) { // find last highlighted block
			if (preg_match('#.*(</?span[^>]*>)#', $source[$i], $m)) {
				if ($m[1] !== '</span>') {
					$spans++;
					$out .= $m[1];
				}

				break;
			}
		}

		$source = array_slice($source, $start, self::DisplayLines, true);
		end($source);
		$numWidth = strlen((string) key($source));

		foreach ($source as $n => $s) {
			$spans += substr_count($s, '<span') - substr_count($s, '</span');
			$s = str_replace(["\r", "\n"], ['', ''], $s);
			preg_match_all('#<[^>]+>#', $s, $tags);
			if ($n == $line) {
				$s = strip_tags($s);
				if ($column) {
					$s = preg_replace(
						'#((?:&.*?;|[^&]){' . ($column - 1) . '})(&.*?;|.)#u',
						'\1<span class="tracy-column-highlight">\2</span>',
						$s . ' ',
						1,
					);
				}
				$out .= sprintf(
					"<span class='tracy-line-highlight'>%{$numWidth}s:    %s</span>\n%s",
					$n,
					$s,
					implode('', $tags[0]),
				);
			} else {
				$out .= sprintf("<span class='tracy-line'>%{$numWidth}s:</span>    %s\n", $n, $s);
			}
		}

		$out .= str_repeat('</span>', $spans) . '</code>';
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
		$source = str_replace('<br />', "\n", $source[1]);
		$out .= self::highlightLine($source, $line, $column);
		$out = str_replace('&nbsp;', ' ', $out);
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
