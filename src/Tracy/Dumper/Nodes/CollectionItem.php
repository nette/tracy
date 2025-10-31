<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper\Nodes;

use Tracy\Dumper\Node;


class CollectionItem implements \JsonSerializable
{
	public function __construct(
		public Node|string|int $key,
		public mixed $value,
		public ?int $refId = null,
		public string|int|null $type = null,
	) {
	}


	public function jsonSerialize(): array
	{
		$res = [$this->key, $this->value];
		foreach (['type', 'refId'] as $k) {
			if ($this->$k !== null) {
				$res[] = $this->$k;
			}
		}
		return $res;
	}
}
