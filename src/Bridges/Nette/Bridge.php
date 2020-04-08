<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bridges\Nette;

use Latte;
use Nette;
use Tracy;
use Tracy\BlueScreen;
use Tracy\Helpers;


/**
 * Bridge for NEON & Latte.
 */
class Bridge
{
	public static function initialize(): void
	{
		$blueScreen = Tracy\Debugger::getBlueScreen();
		$blueScreen->addPanel([self::class, 'renderLatteError']);
		$blueScreen->addAction([self::class, 'renderLatteUnknownMacro']);
		$blueScreen->addAction([self::class, 'renderMemberAccessException']);
		$blueScreen->addPanel([self::class, 'renderNeonError']);
	}


	public static function renderLatteError(?\Throwable $e): ?array
	{
		if ($e instanceof Latte\CompileException) {
			return [
				'tab' => 'Template',
				'panel' => (preg_match('#\n|\?#', $e->sourceName)
						? ''
						: '<p>'
							. (@is_file($e->sourceName) // @ - may trigger error
								? '<b>File:</b> ' . Helpers::editorLink($e->sourceName, $e->sourceLine)
								: '<b>' . htmlspecialchars($e->sourceName . ($e->sourceLine ? ':' . $e->sourceLine : '')) . '</b>')
							. '</p>')
					. '<pre class=code><div>'
					. BlueScreen::highlightLine(htmlspecialchars($e->sourceCode, ENT_IGNORE, 'UTF-8'), $e->sourceLine)
					. '</div></pre>',
			];
		} elseif ($e && strpos($file = $e->getFile(), '.latte--')) {
			$lines = file($file);
			if (preg_match('#// source: (\S+\.latte)#', $lines[1], $m) && @is_file($m[1])) { // @ - may trigger error
				$templateFile = $m[1];
				$templateLine = $e->getLine() && preg_match('#/\* line (\d+) \*/#', $lines[$e->getLine() - 1], $m) ? (int) $m[1] : 0;
				return [
					'tab' => 'Template',
					'panel' => '<p><b>File:</b> ' . Helpers::editorLink($templateFile, $templateLine) . '</p>'
						. ($templateLine === null
							? ''
							: BlueScreen::highlightFile($templateFile, $templateLine)),
				];
			}
		}
		return null;
	}


	public static function renderLatteUnknownMacro(?\Throwable $e): ?array
	{
		if (
			$e instanceof Latte\CompileException
			&& @is_file($e->sourceName) // @ - may trigger error
			&& (preg_match('#Unknown macro (\{\w+)\}, did you mean (\{\w+)\}\?#A', $e->getMessage(), $m)
				|| preg_match('#Unknown attribute (n:\w+), did you mean (n:\w+)\?#A', $e->getMessage(), $m))
		) {
			return [
				'link' => Helpers::editorUri($e->sourceName, $e->sourceLine, 'fix', $m[1], $m[2]),
				'label' => 'fix it',
			];
		}
		return null;
	}


	public static function renderMemberAccessException(?\Throwable $e): ?array
	{
		if (!$e instanceof Nette\MemberAccessException && !$e instanceof \LogicException) {
			return null;
		}
		$loc = $e instanceof Nette\MemberAccessException ? $e->getTrace()[1] : $e->getTrace()[0];
		if (preg_match('#Cannot (?:read|write to) an undeclared property .+::\$(\w+), did you mean \$(\w+)\?#A', $e->getMessage(), $m)) {
			return [
				'link' => Helpers::editorUri($loc['file'], $loc['line'], 'fix', '->' . $m[1], '->' . $m[2]),
				'label' => 'fix it',
			];
		}
		if (preg_match('#Call to undefined (static )?method .+::(\w+)\(\), did you mean (\w+)\(\)?#A', $e->getMessage(), $m)) {
			$operator = $m[1] ? '::' : '->';
			return [
				'link' => Helpers::editorUri($loc['file'], $loc['line'], 'fix', $operator . $m[2] . '(', $operator . $m[3] . '('),
				'label' => 'fix it',
			];
		}
		return null;
	}


	public static function renderNeonError(?\Throwable $e): ?array
	{
		if (
			$e instanceof Nette\Neon\Exception
			&& preg_match('#line (\d+)#', $e->getMessage(), $m)
			&& ($trace = Helpers::findTrace($e->getTrace(), [Nette\Neon\Decoder::class, 'decode']))
		) {
			return [
				'tab' => 'NEON',
				'panel' => ($trace2 = Helpers::findTrace($e->getTrace(), [Nette\DI\Config\Adapters\NeonAdapter::class, 'load']))
					? '<p><b>File:</b> ' . Helpers::editorLink($trace2['args'][0], (int) $m[1]) . '</p>'
						. self::highlightNeon(file_get_contents($trace2['args'][0]), (int) $m[1])
					: self::highlightNeon($trace['args'][0], (int) $m[1]),
			];
		}
		return null;
	}


	private static function highlightNeon(string $code, int $line): string
	{
		$code = htmlspecialchars($code, ENT_IGNORE, 'UTF-8');
		$code = str_replace(' ', "<span class='tracy-dump-whitespace'>·</span>", $code);
		$code = str_replace("\t", "<span class='tracy-dump-whitespace'>→   </span>", $code);
		return '<pre class=code><div>'
			. BlueScreen::highlightLine($code, $line)
			. '</div></pre>';
	}
}
