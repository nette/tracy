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
		return $this->data
			? Panel::capture(
				function () { require __DIR__ . '/panels/dumps.tab.phtml'; },
				function () {
					$data = $this->data;
					require __DIR__ . '/panels/dumps.panel.phtml';
				},
				$this->getId()
			)
			: null;
	}


	public function getId(): string
	{
		return 'Tracy:dumps';
	}
}
