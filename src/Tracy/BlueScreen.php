<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Diagnostics;

use Nette;



/**
 * Red BlueScreen.
 *
 * @author     David Grudl
 * @internal
 */
class BlueScreen extends Nette\Object
{
	/** @var array */
	private $panels = array();



	/**
	 * Add custom panel.
	 * @param  callback
	 * @param  string
	 * @return void
	 */
	public function addPanel($panel, $id = NULL)
	{
		if ($id === NULL) {
			$this->panels[] = $panel;
		} else {
			$this->panels[$id] = $panel;
		}
	}



	/**
	 * Renders blue screen.
	 * @param  \Exception
	 * @return void
	 */
	public function render(\Exception $exception)
	{
		$panels = $this->panels;
		require __DIR__ . '/templates/bluescreen.phtml';
	}



	/**
	 * Returns syntax highlighted source code.
	 * @param  string
	 * @param  int
	 * @param  int
	 * @return string
	 */
	public static function highlightFile($file, $line, $count = 15)
	{
		if (function_exists('ini_set')) {
			ini_set('highlight.comment', '#999; font-style: italic');
			ini_set('highlight.default', '#000');
			ini_set('highlight.html', '#06B');
			ini_set('highlight.keyword', '#D24; font-weight: bold');
			ini_set('highlight.string', '#080');
		}

		$start = max(1, $line - floor($count / 2));

		$source = @file_get_contents($file); // intentionally @
		if (!$source) {
			return;
		}
		$source = explode("\n", highlight_string($source, TRUE));
		$spans = 1;
		$out = $source[0]; // <code><span color=highlight.html>
		$source = explode('<br />', $source[1]);
		array_unshift($source, NULL);

		$i = $start; // find last highlighted block
		while (--$i >= 1) {
			if (preg_match('#.*(</?span[^>]*>)#', $source[$i], $m)) {
				if ($m[1] !== '</span>') {
					$spans++; $out .= $m[1];
				}
				break;
			}
		}

		$source = array_slice($source, $start, $count, TRUE);
		end($source);
		$numWidth = strlen((string) key($source));

		foreach ($source as $n => $s) {
			$spans += substr_count($s, '<span') - substr_count($s, '</span');
			$s = str_replace(array("\r", "\n"), array('', ''), $s);
			preg_match_all('#<[^>]+>#', $s, $tags);
			if ($n === $line) {
				$out .= sprintf(
					"<span class='highlight'>%{$numWidth}s:    %s\n</span>%s",
					$n,
					strip_tags($s),
					implode('', $tags[0])
				);
			} else {
				$out .= sprintf("<span class='line'>%{$numWidth}s:</span>    %s\n", $n, $s);
			}
		}
		return $out . str_repeat('</span>', $spans) . '</code>';
	}

}
