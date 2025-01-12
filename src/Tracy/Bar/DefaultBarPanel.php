<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


/**
 * IBarPanel implementation helper.
 * @internal
 */
#[\AllowDynamicProperties]
class DefaultBarPanel implements IBarPanel
{
	public mixed $data = null;


	public function __construct(
		private readonly string $id,
	) {
	}


	/**
	 * Renders HTML code for custom tab.
	 */
	public function getTab(): string
	{
		return Helpers::capture(function () {
			$data = $this->data;
			require __DIR__ . "/dist/{$this->id}.tab.phtml";
		});
	}


	/**
	 * Renders HTML code for custom panel.
	 */
	public function getPanel(): string
	{
		return Helpers::capture(function () {
			if (is_file(__DIR__ . "/dist/{$this->id}.panel.phtml")) {
				$data = $this->data;
				require __DIR__ . "/dist/{$this->id}.panel.phtml";
			}
		});
	}
}
