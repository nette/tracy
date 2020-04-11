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
final class Structure implements \JsonSerializable
{
	/** @var string */
	public $name;

	/** @var ?int */
	public $depth;

	/** @var mixed */
	public $ref;

	/** @var ?\stdClass */
	public $editor;

	/** @var ?array */
	public $items;


	public function __construct(string $name, int $depth = null, $ref = null)
	{
		$this->name = $name;
		$this->depth = $depth;
		$this->ref = $ref; // to be not released by garbage collector in collecting mode
	}


	public function jsonSerialize()
	{
		static $keys = ['name', 'editor', 'items'];
		$res = [];
		foreach ($keys as $k) {
			if (isset($this->$k)) {
				$res[$k] = $this->$k;
			}
		}
		return $res;
	}
}
