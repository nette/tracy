<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bar;


/**
 * @internal
 */
final class DumpsExtension extends Extension
{
	/** @var array */
	public $data;


	public function getPanel(): ?Panel
	{
		if (!$this->data) {
			return null;
		}
		return new Panel(
			Helpers::capture(function () { require __DIR__ . '/panels/dumps.tab.phtml'; }),
			Helpers::capture(function () {
				$data = $this->data;
				require __DIR__ . '/panels/dumps.panel.phtml';
			}),
			$this->getId()
		);
	}


	public function getId(): string
	{
		return 'Tracy:dumps';
	}
}
