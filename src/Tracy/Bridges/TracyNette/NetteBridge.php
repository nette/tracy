<?php

/**
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Tracy\Bridges\TracyNette;

use Nette,
	Tracy,
	Tracy\Helpers,
	Tracy\BlueScreen,
	Latte;


/**
 * Initializes Tracy
 */
class NetteBridge
{

	public static function initialize()
	{
		$blueScreen = Tracy\Debugger::getBlueScreen();
		if (preg_match('#(.+)/Tracy/Bridges/TracyNette$#', strtr(__DIR__, '\\', '/'), $m)) {
			if (preg_match('#(.+)/tracy/tracy/src$#', $m[1], $m2)) {
				$blueScreen->collapsePaths[] = "$m2[1]/nette";
				$blueScreen->collapsePaths[] = "$m2[1]/latte";
			}
		}

		if (class_exists('Nette\Framework')) {
			$bar = Tracy\Debugger::getBar();
			$bar->info[] = $blueScreen->info[] = 'Nette Framework ' . Nette\Framework::VERSION . ' (' . Nette\Framework::REVISION . ')';
		}

		$blueScreen->addPanel(function($e) {
			if ($e instanceof Latte\CompileException) {
				return array(
					'tab' => 'Template',
					'panel' => '<p>' . (is_file($e->sourceName) ? '<b>File:</b> ' . Helpers::editorLink($e->sourceName, $e->sourceLine) : htmlspecialchars($e->sourceName)) . '</p>'
						. ($e->sourceCode ? '<pre>' . BlueScreen::highlightLine(htmlspecialchars($e->sourceCode), $e->sourceLine) . '</pre>' : ''),
				);
			} elseif ($e instanceof Nette\Neon\Exception && preg_match('#line (\d+)#', $e->getMessage(), $m)) {
				if ($item = Helpers::findTrace($e->getTrace(), 'Nette\DI\Config\Adapters\NeonAdapter::load')) {
					return array(
						'tab' => 'NEON',
						'panel' => '<p><b>File:</b> ' . Helpers::editorLink($item['args'][0], $m[1]) . '</p>'
							. BlueScreen::highlightFile($item['args'][0], $m[1])
					);
				} elseif ($item = Helpers::findTrace($e->getTrace(), 'Nette\Neon\Decoder::decode')) {
					return array(
						'tab' => 'NEON',
						'panel' => BlueScreen::highlightPhp($item['args'][0], $m[1])
					);
				}
			}
		});
	}

}
