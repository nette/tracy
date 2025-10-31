<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper\Nodes;


/**
 * Represents a resource value.
 */
final class ResourceNode extends CollectionNode
{
	public function __construct(
		public string $description,
		/** (format: "r{n}") */
		public string $id,
	) {
		$this->items = [];
	}


	public function jsonSerialize(): array
	{
		return [
			'resource' => $this->description,
			'items' => $this->items,
		];
	}
}
