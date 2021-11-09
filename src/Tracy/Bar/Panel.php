<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bar;


final class Panel
{
	/** @var string */
	public $labelHtml;

	/** @var ?string */
	public $panelHtml;

	/** @var string */
	public $id;


	public function __construct(string $labelHtml, ?string $panelHtml, string $id)
	{
		$this->labelHtml = $labelHtml;
		$this->panelHtml = $panelHtml;
		$this->id = $id;
	}
}
