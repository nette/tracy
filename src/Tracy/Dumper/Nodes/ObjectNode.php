<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper\Nodes;


/**
 * Represents an object value.
 */
final class ObjectNode extends CollectionNode
{
	public const
		PropertyPublic = 0,
		PropertyProtected = 1,
		PropertyPrivate = 2,
		PropertyDynamic = 3,
		PropertyVirtual = 4;

	/** Unique identifier for reference tracking  */
	public ?int $id = null;

	/** Original object (prevents garbage collection) */
	public ?object $holder = null;

	/** @var object{file: string, line: int, url: string}|null */
	public ?\stdClass $editor = null;


	public function __construct(
		public string $className = '',
	) {
	}


	public function jsonSerialize(): array
	{
		$res = ['object' => $this->className];
		foreach (['length', 'editor', 'items', 'collapsed'] as $k) {
			if ($this->$k !== null) {
				$res[$k] = $this->$k;
			}
		}

		return $res;
	}
}
