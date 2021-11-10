<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bridges\Nette;

use Tracy;


/**
 * Bridge for NEON & Latte.
 */
class Bridge
{
	public static function initialize(): void
	{
		$blueScreen = Tracy\Debugger::getBlueScreen();
		$blueScreen->addExtension(new BlueScreenExtensionForNette);
		$blueScreen->addExtension(new BlueScreenExtensionForLatte);
		$blueScreen->addExtension(new BlueScreenExtensionForNeon);
	}
}
