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
	public const
		PROP_PUBLIC = 0,
		PROP_PROTECTED = 1,
		PROP_PRIVATE = 2;

	/** @var string */
	public $type;

	public $value;

	/** @var ?int */
	public $length;

	/** @var ?int */
	public $depth;

	/** @var int|string */
	public $id;

	/** @var object */
	public $holder;

	/** @var ?array */
	public $items;

	/** @var ?\stdClass */
	public $editor;


	public function __construct(string $type, $value = null, int $length = null)
	{
		$this->type = $type;
		$this->value = $value;
		$this->length = $length;
	}


	public function jsonSerialize(): array
	{
		$res = [$this->type => $this->value];
		foreach (['length', 'editor', 'items'] as $k) {
			if ($this->$k !== null) {
				$res[$k] = $this->$k;
			}
		}
		return $res;
	}
}
