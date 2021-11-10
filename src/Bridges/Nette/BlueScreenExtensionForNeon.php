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
use Tracy\BlueScreen\Panel;
use Tracy\Helpers;


class BlueScreenExtensionForNeon extends Tracy\Bar\Extension
{
	public function getTopPanel(\Throwable $e): ?Panel
	{
		if (
			$e instanceof Nette\Neon\Exception
			&& preg_match('#line (\d+)#', $e->getMessage(), $m)
			&& ($trace = Helpers::findTrace($e->getTrace(), [Nette\Neon\Decoder::class, 'decode']))
		) {
			return new Panel(
				'NEON',
				($trace2 = Helpers::findTrace($e->getTrace(), [Nette\DI\Config\Adapters\NeonAdapter::class, 'load']))
					? '<p><b>File:</b> ' . Helpers::editorLink($trace2['args'][0], (int) $m[1]) . '</p>'
						. $this->highlightNeon(file_get_contents($trace2['args'][0]), (int) $m[1])
					: $this->highlightNeon($trace['args'][0], (int) $m[1])
			);
		}
		return null;
	}


	private function highlightNeon(string $code, int $line): string
	{
		$code = htmlspecialchars($code, ENT_IGNORE, 'UTF-8');
		$code = str_replace(' ', "<span class='tracy-dump-whitespace'>·</span>", $code);
		$code = str_replace("\t", "<span class='tracy-dump-whitespace'>→   </span>", $code);
		return '<pre class=code><div>'
			. BlueScreen::highlightLine($code, $line)
			. '</div></pre>';
	}
}
