<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper\Nodes;

use Tracy\Dumper\Node;


/**
 * Represents string values.
 */
final class StringNode extends Node
{
	public function __construct(
		/** HTML-escaped string content */
		public string $content,
		/** Length in characters (UTF-8) or bytes (binary) */
		public int $length,
		/** Whether this is binary data vs UTF-8 text */
		public bool $binary = false,
	) {
	}


	public function jsonSerialize(): array
	{
		return [
			$this->binary ? 'bin' : 'string' => $this->content,
			'length' => $this->length,
		];
	}
}
