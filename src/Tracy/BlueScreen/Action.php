<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\BlueScreen;


final class Action
{
	/** @var string */
	public $label;

	/** @var string */
	public $url;

	/** @var bool */
	public $newWindow;


	public function __construct(string $label, string $url, bool $newWindow = false)
	{
		$this->label = $label;
		$this->url = $url;
		$this->newWindow = $newWindow;
	}
}
