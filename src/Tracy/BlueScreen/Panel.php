<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\BlueScreen;


final class Panel
{
	/** @var string */
	public $label;

	/** @var string */
	public $panelHtml;

	/** @var bool */
	public $collapsed;


	public function __construct(string $label, string $panelHtml, bool $collapsed = false)
	{
		$this->label = $label;
		$this->panelHtml = $panelHtml;
		$this->collapsed = $collapsed;
	}
}
