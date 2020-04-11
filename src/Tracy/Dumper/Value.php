<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper;


/**
 * @internal
 */
final class Value implements \JsonSerializable
{
	/** @var string */
	public $type;

	public $value;


	public function __construct(string $type, $value)
	{
		$this->type = $type;
		$this->value = $value;
	}


	public function jsonSerialize()
	{
		return [$this->type => $this->value];
	}
}
