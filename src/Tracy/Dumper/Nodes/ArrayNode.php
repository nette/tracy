<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper\Nodes;


/**
 * Represents an array value.
 */
final class ArrayNode extends CollectionNode
{
	/** Unique identifier for reference tracking. Format: "p{n}" */
	public ?string $id = null;


	public function jsonSerialize(): array
	{
		$res = ['array' => null];
		foreach (['length', 'items', 'collapsed'] as $k) {
			if ($this->$k !== null) {
				$res[$k] = $this->$k;
			}
		}

		return $res;
	}
}
