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

	/** @var ?int */
	public $length;


	public function __construct(string $type, $value, int $length = null)
	{
		$this->type = $type;
		$this->value = $value;
		$this->length = $length;
	}


	public function jsonSerialize()
	{
		$res = [$this->type => $this->value];
		if ($this->length) {
			$res['length'] = $this->length;
		}
		return $res;
	}
}
