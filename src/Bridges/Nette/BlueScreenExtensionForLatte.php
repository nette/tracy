<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bridges\Nette;

use Latte;
use Tracy;
use Tracy\BlueScreen;
use Tracy\BlueScreen\Action;
use Tracy\BlueScreen\Panel;
use Tracy\Helpers;


class BlueScreenExtensionForLatte extends Tracy\Bar\Extension
{
	public function getTopPanel(\Throwable $e): ?Panel
	{
		if ($e instanceof Latte\CompileException && $e->sourceName) {
			return new Panel(
				'Template',
				(preg_match('#\n|\?#', $e->sourceName)
						? ''
						: '<p>'
							. (@is_file($e->sourceName) // @ - may trigger error
								? '<b>File:</b> ' . Helpers::editorLink($e->sourceName, $e->sourceLine)
								: '<b>' . htmlspecialchars($e->sourceName . ($e->sourceLine ? ':' . $e->sourceLine : '')) . '</b>')
							. '</p>')
					. '<pre class=code><div>'
					. BlueScreen::highlightLine(htmlspecialchars($e->sourceCode, ENT_IGNORE, 'UTF-8'), $e->sourceLine)
					. '</div></pre>'
			);

		} elseif (strpos($file = $e->getFile(), '.latte--')) {
			$lines = file($file);
			if (preg_match('#// source: (\S+\.latte)#', $lines[1], $m) && @is_file($m[1])) { // @ - may trigger error
				$templateFile = $m[1];
				$templateLine = $e->getLine() && preg_match('#/\* line (\d+) \*/#', $lines[$e->getLine() - 1], $m) ? (int) $m[1] : 0;
				return new Panel(
					'Template',
					'<p><b>File:</b> ' . Helpers::editorLink($templateFile, $templateLine) . '</p>'
						. ($templateLine === null
							? ''
							: BlueScreen::highlightFile($templateFile, $templateLine))
				);
			}
		}
		return null;
	}


	public function getAction(\Throwable $e): ?Action
	{
		if (
			$e instanceof Latte\CompileException
			&& $e->sourceName
			&& @is_file($e->sourceName) // @ - may trigger error
			&& (preg_match('#Unknown macro (\{\w+)\}, did you mean (\{\w+)\}\?#A', $e->getMessage(), $m)
				|| preg_match('#Unknown attribute (n:\w+), did you mean (n:\w+)\?#A', $e->getMessage(), $m))
		) {
			return new Action(
				'fix it',
				Helpers::editorUri($e->sourceName, $e->sourceLine, 'fix', $m[1], $m[2])
			);
		}
		return null;
	}
}
