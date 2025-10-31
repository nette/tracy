<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper\Nodes;

use Tracy\Dumper\Node;


final class NumberNode extends Node
{
	public function __construct(
		public string $value,
	) {
	}


	public function jsonSerialize(): array
	{
		return ['number' => $this->value];
	}
}
