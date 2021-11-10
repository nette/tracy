<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bar;

use Tracy\Helpers;


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


	public static function capture(callable $label, callable $panel, string $id)
	{
		return new self(Helpers::capture($label), Helpers::capture($panel), $id);
	}
}
