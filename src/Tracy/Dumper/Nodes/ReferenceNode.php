<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper\Nodes;

use Tracy\Dumper\Node;


/**
 * Represents a reference to another value (for cycle detection and deduplication).
 */
final class ReferenceNode extends Node
{
	public function __construct(
		public int|string $targetId,
	) {
	}


	public function jsonSerialize(): array
	{
		return ['ref' => $this->targetId];
	}
}
