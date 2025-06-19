<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;

use const PHP_SESSION_ACTIVE;


class NativeSession implements SessionStorage
{
	public function isAvailable(): bool
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}


	public function &getData(): array
	{
		settype($_SESSION['_tracy'], 'array');
		return $_SESSION['_tracy'];
	}
}
