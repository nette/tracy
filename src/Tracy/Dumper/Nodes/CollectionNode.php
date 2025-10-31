<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper\Nodes;

use Tracy\Dumper\Node;


abstract class CollectionNode extends Node
{
	/** @var CollectionItem[]|null */
	public ?array $items = null;

	/** Total number of elements (may exceed displayed items) */
	public ?int $length = null;

	/** Explicit collapse state for HTML rendering. null = auto-decide based on size and depth */
	public ?bool $collapsed = null;

	/** Nesting depth level */
	public int $depth = 0;
}
