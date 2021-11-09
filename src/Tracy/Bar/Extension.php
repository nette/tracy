<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bar;


abstract class Extension
{
	public function getPanel(): ?Panel
	{
		return null;
	}


	public function getId(): string
	{
		return '';
	}
}
