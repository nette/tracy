<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bridges\Nette;

use Nette;
use Tracy;
use Tracy\BlueScreen;
use Tracy\Helpers;


/**
 * Bridge for NEON.
 */
class Bridge
{
	public static function initialize(): void
	{
		$blueScreen = Tracy\Debugger::getBlueScreen();
		$blueScreen->addAction([self::class, 'renderMemberAccessException']);
		$blueScreen->addPanel([self::class, 'renderNeonError']);
	}


	public static function renderMemberAccessException(?\Throwable $e): ?array
	{
		if (!$e instanceof Nette\MemberAccessException && !$e instanceof \LogicException) {
			return null;
		}

		$loc = $e->getTrace()[$e instanceof Nette\MemberAccessException ? 1 : 0];
		if (!isset($loc['file'])) {
			return null;
		}

		$loc = Tracy\Debugger::mapSource($loc['file'], $loc['line']) ?? $loc;
		if (preg_match('#Cannot (?:read|write to) an undeclared property .+::\$(\w+), did you mean \$(\w+)\?#A', $e->getMessage(), $m)) {
			return [
				'link' => Helpers::editorUri($loc['file'], $loc['line'], 'fix', '->' . $m[1], '->' . $m[2]),
				'label' => 'fix it',
			];
		} elseif (preg_match('#Call to undefined (static )?method .+::(\w+)\(\), did you mean (\w+)\(\)?#A', $e->getMessage(), $m)) {
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
		if (!$e instanceof Nette\Neon\Exception || !preg_match('#line (\d+)#', $e->getMessage(), $m)) {
			return null;

		} elseif ($trace = Helpers::findTrace($e->getTrace(), [Nette\Neon\Decoder::class, 'decodeFile'])
			?? Helpers::findTrace($e->getTrace(), [Nette\DI\Config\Adapters\NeonAdapter::class, 'load'])
		) {
			$panel = '<p><b>File:</b> ' . Helpers::editorLink($trace['args'][0], (int) $m[1]) . '</p>'
				. self::highlightNeon(file_get_contents($trace['args'][0]), (int) $m[1]);

		} elseif ($trace = Helpers::findTrace($e->getTrace(), [Nette\Neon\Decoder::class, 'decode'])) {
			$panel = self::highlightNeon($trace['args'][0], (int) $m[1]);
		}

		return isset($panel) ? ['tab' => 'NEON', 'panel' => $panel] : null;
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
