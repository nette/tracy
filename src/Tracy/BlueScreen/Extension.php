<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\BlueScreen;

use Throwable;


abstract class Extension
{
	public function getTopPanel(Throwable $e): ?Panel
	{
		return null;
	}


	public function getMiddlePanel(Throwable $e): ?Panel
	{
		return null;
	}


	public function getBottomPanel(Throwable $e): ?Panel
	{
		return null;
	}


	public function getInfoPanel(): ?Panel
	{
		return null;
	}


	public function getAction(Throwable $e): ?Action
	{
		return null;
	}


	public function getId(): string
	{
		return '';
	}
}
