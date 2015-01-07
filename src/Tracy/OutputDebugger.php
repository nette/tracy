<?php

/**
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Tracy;

use Tracy;


/**
 * @author     David Grudl
 */
class OutputDebugger
{
	const BOM = "\xEF\xBB\xBF";

	/** @var array of [file, line, output, stack] */
	private $list = array();


	public static function enable()
	{
		$me = new static;
		$me->start();
	}


	public function start()
	{
		foreach (get_included_files() as $file) {
			if (fread(fopen($file, 'r'), 3) === self::BOM) {
				$this->list[] = array($file, 1, self::BOM);
			}
		}
		ob_start(array($this, 'handler'), PHP_VERSION_ID >= 50400 ? 1 : 2);
	}


	/** @internal */
	public function handler($s, $phase)
	{
		$trace = debug_backtrace(PHP_VERSION_ID >= 50306 ? DEBUG_BACKTRACE_IGNORE_ARGS : FALSE);
		if (isset($trace[0]['file'], $trace[0]['line'])) {
			$stack = $trace;
			unset($stack[0]['line'], $stack[0]['args']);
			$i = count($this->list);
			if ($i && $this->list[$i - 1][3] === $stack) {
				$this->list[$i - 1][2] .= $s;
			} else {
				$this->list[] = array($trace[0]['file'], $trace[0]['line'], $s, $stack);
			}
		}
		if ($phase === PHP_OUTPUT_HANDLER_FINAL) {
			return $this->renderHtml();
		}
	}


	private function renderHtml()
	{
		$res = '<style>code, pre {white-space:nowrap} a {text-decoration:none} pre {color:gray;display:inline} big {color:red}</style><code>';
		foreach ($this->list as $item) {
			$stack = array();
			foreach (array_slice($item[3], 1) as $t) {
				$t += array('class' => '', 'type' => '', 'function' => '');
				$stack[] = "$t[class]$t[type]$t[function]()"
					. (isset($t['file'], $t['line']) ? ' in ' . basename($t['file']) . ":$t[line]" : '');
			}

			$res .= Helpers::editorLink($item[0], $item[1]) . ' '
				. '<span title="' . htmlspecialchars(implode("\n", $stack), ENT_IGNORE | ENT_QUOTES, 'UTF-8') . '">'
				. str_replace(self::BOM, '<big>BOM</big>', Dumper::toHtml($item[2]))
				. "</span><br>\n";
		}
		return $res . '</code>';
	}

}
