<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bar;

use Tracy\Debugger;
use Tracy\Helpers;


/**
 * @internal
 */
final class InfoExtension extends Extension
{
	/** @var array */
	public $cpuUsage;

	/** @var float */
	public $time;

	/** @var array */
	public $data;


	public function getPanel(): ?Panel
	{
		$time = microtime(true) - Debugger::$time;
		return new Panel(
			Helpers::capture(function () use ($time) {
				require __DIR__ . '/panels/info.tab.phtml';
			}),
			Helpers::capture(function () use ($time) {
				$cpuUsage = $this->cpuUsage;
				$data = $this->data;
				require __DIR__ . '/panels/info.panel.phtml';
			}),
			$this->getId()
		);
	}


	public function getId(): string
	{
		return 'Tracy:info';
	}
}
